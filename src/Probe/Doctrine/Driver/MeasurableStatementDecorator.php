<?php

declare(strict_types=1);

namespace Batch\Metrics\Probe\Doctrine\Driver;

use Batch\Metrics\Collector as MetricCollector;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * A decorator wrapping a Statement instance and intercepting method calls to measure the following metrics:
 *
 *   - mysql_query_error
 *   - mysql_query_execution_time
 */
final class MeasurableStatementDecorator implements \IteratorAggregate, Statement, Result
{
    /**
     * @var Statement&Result
     */
    private $decoratedStatement;

    private MetricCollector $metrics;
    private string $operation;
    private string $host;

    /**
     * @param Statement&Result $decoratedStatement
     */
    public function __construct($decoratedStatement, MetricCollector $metrics, string $operation, string $host)
    {
        $this->decoratedStatement = $decoratedStatement;
        $this->metrics = $metrics;
        $this->operation = $operation;
        $this->host = $host;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        return $this->decoratedStatement->bindValue($param, $value, $type);
    }

    public function bindParam($column, &$variable, $type = ParameterType::STRING, $length = null)
    {
        return $this->decoratedStatement->bindParam($column, $variable, $type, $length);
    }

    public function errorCode()
    {
        return $this->decoratedStatement->errorCode();
    }

    public function errorInfo()
    {
        return $this->decoratedStatement->errorInfo();
    }

    public function execute($params = null)
    {
        $start = \microtime(true);

        try {
            $result = $this->decoratedStatement->execute($params);
        } catch (\Throwable $exception) {
            $this->metrics->incrementCounter('mysql_query_error', [$this->host]);
            throw $exception;
        } finally {
            $elapsed = \microtime(true) - $start;
            $labels = [$this->operation, $this->host, true];
            $this->metrics->observeHistogram('mysql_query_execution_time', $elapsed, $labels);
        }

        return $result;
    }

    public function rowCount()
    {
        return $this->decoratedStatement->rowCount();
    }

    public function closeCursor()
    {
        return $this->decoratedStatement->closeCursor();
    }

    public function columnCount()
    {
        return $this->decoratedStatement->columnCount();
    }

    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        return $this->decoratedStatement->setFetchMode($fetchMode, $arg2, $arg3);
    }

    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->decoratedStatement->fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }

    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        return $this->decoratedStatement->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
    }

    public function fetchColumn($columnIndex = 0)
    {
        return $this->decoratedStatement->fetchColumn($columnIndex);
    }

    public function fetchNumeric()
    {
        return $this->decoratedStatement->fetchNumeric();
    }

    public function fetchAssociative()
    {
        return $this->decoratedStatement->fetchAssociative();
    }

    public function fetchOne()
    {
        return $this->decoratedStatement->fetchOne();
    }

    public function fetchAllNumeric(): array
    {
        return $this->decoratedStatement->fetchAllNumeric();
    }

    public function fetchAllAssociative(): array
    {
        return $this->decoratedStatement->fetchAllAssociative();
    }

    public function fetchFirstColumn(): array
    {
        return $this->decoratedStatement->fetchFirstColumn();
    }

    public function free(): void
    {
        $this->decoratedStatement->free();
    }

    public function getIterator(): \Traversable
    {
        // @phpstan-ignore-next-line
        return $this->decoratedStatement->getIterator();
    }
}
