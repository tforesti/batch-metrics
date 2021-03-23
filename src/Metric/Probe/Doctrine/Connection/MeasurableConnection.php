<?php

declare(strict_types=1);

namespace App\Metric\Probe\Doctrine\Connection;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Doctrine\Driver\MeasurableDriverDecorator;
use App\Metric\Probe\Doctrine\Driver\MeasurableStatementDecorator;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Safe\Exceptions\StringsException;

use function Safe\substr;

/**
 * A child of the Connection class which decorates the internal drivers/statements and intercepts method calls to
 * measure the following metrics:
 *
 *   - mysql_query_error
 *   - mysql_query_execution_time
 *   - mysql_transaction_exec
 *   - mysql_transaction_pending
 */
final class MeasurableConnection extends Connection
{
    private ?MetricCollector $metrics = null;

    public function setMetricCollector(MetricCollector $metrics): void
    {
        $this->metrics = $metrics;
        $this->_driver = new MeasurableDriverDecorator($this->_driver, $this->metrics, $this->getHost());
    }

    private static function getOperationFromSqlQuery(string $query): string
    {
        try {
            $operation = \strtolower(\explode(' ', substr(\ltrim($query, "( \t\n\r\0\x0B"), 0, 15), 2)[0]);
            return \strlen($operation) !== 0 ? $operation : 'unknown';
        } catch (StringsException $exception) {
            return 'unknown';
        }
    }

    public function getHost()
    {
        return $this->getParams()['host'] ?? null;
    }

    public function prepare($prepareString)
    {
        $statement = parent::prepare($prepareString);

        if ($this->metrics !== null) {
            return new MeasurableStatementDecorator(
                $statement,
                $this->metrics,
                self::getOperationFromSqlQuery($prepareString),
                $this->getHost()
            );
        }

        return $statement;
    }

    /** @return mixed */
    private function measureQueryExecution(string $sqlQuery, callable $runner)
    {
        $start = \microtime(true);

        try {
            $result = $runner();
        } catch (\Throwable $exception) {
            if ($this->metrics !== null) {
                $this->metrics->incrementCounter('mysql_query_error', [$this->getHost()]);
            }
            throw $exception;
        } finally {
            $elapsed = \microtime(true) - $start;
            $labels = [self::getOperationFromSqlQuery($sqlQuery), $this->getHost(), false];
            if ($this->metrics !== null) {
                $this->metrics->observeHistogram('mysql_query_execution_time', $elapsed, $labels);
            }
        }

        return $result;
    }

    public function executeQuery($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        return $this->measureQueryExecution($query, fn() => parent::executeQuery($query, $params, $types, $qcp));
    }

    public function query()
    {
        $args = \func_get_args();
        return $this->measureQueryExecution($args[0] ?? '', fn() => parent::query(...$args));
    }

    public function exec($statement)
    {
        return $this->measureQueryExecution($statement, fn() => parent::exec($statement));
    }

    public function beginTransaction()
    {
        if ($this->metrics !== null) {
            $this->metrics->incrementGauge('mysql_transaction_pending', [$this->getHost()]);
        }

        return parent::beginTransaction();
    }

    public function commit()
    {
        if ($this->metrics !== null) {
            $this->metrics->decrementGauge('mysql_transaction_pending', [$this->getHost()]);
        }

        try {
            $result = parent::commit();
            if ($this->metrics !== null) {
                $this->metrics->incrementCounter('mysql_transaction_exec', [$this->getHost(), 'success']);
            }
            return $result;
        } catch (\Throwable $exception) {
            if ($this->metrics !== null) {
                $this->metrics->incrementCounter('mysql_transaction_exec', [$this->getHost(), 'fail']);
            }
            throw $exception;
        }
    }

    public function rollBack()
    {
        if ($this->metrics !== null) {
            $this->metrics->decrementGauge('mysql_transaction_pending', [$this->getHost()]);
            $this->metrics->incrementCounter('mysql_transaction_exec', [$this->getHost(), 'rollback']);
        }

        return parent::rollBack();
    }
}
