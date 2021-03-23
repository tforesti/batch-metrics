<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Probe\Doctrine\Connection;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Doctrine\Connection\MeasurableConnection;
use App\Metric\Probe\Doctrine\Driver\MeasurableStatementDecorator;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

class MeasurableConnectionTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    private MeasurableConnection $connection;

    public function setUp(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $pdo->method('prepare')->willReturn($statement);
        $pdo->method('query')->willReturn($statement);

        $driver = $this->createMock(Driver::class);
        $driver->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $this->metrics = $this->createMock(MetricCollector::class);
        $this->connection = new MeasurableConnection(['pdo' => $pdo, 'host' => 'localhost'], $driver);
    }

    public function testMetricCollectorIsNotRequiredToUseTheDecorator(): void
    {
        // @phpstan-ignore-next-line
        static::assertNotInstanceOf(MeasurableStatementDecorator::class, $this->connection->prepare(''));

        $this->connection->query();
        $this->connection->exec('');
        $this->connection->beginTransaction();
        $this->connection->commit();
        $this->connection->beginTransaction();
        $this->connection->rollBack();
    }

    public function testPrepareMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);

        // @phpstan-ignore-next-line
        static::assertInstanceOf(MeasurableStatementDecorator::class, $this->connection->prepare(''));
    }

    public function testExecuteQueryMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);

        $this->metrics->expects(static::once())->method('observeHistogram')
            ->with(static::equalTo('mysql_query_execution_time'));

        $this->connection->executeQuery('');
    }

    public function testQueryMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);

        $this->metrics->expects(static::once())->method('observeHistogram')
            ->with(static::equalTo('mysql_query_execution_time'));

        $this->connection->query('');
    }

    public function testExecMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);

        $this->metrics->expects(static::once())->method('observeHistogram')
            ->with(static::equalTo('mysql_query_execution_time'));

        $this->connection->exec('');
    }

    public function testBeginTransactionMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);
        $this->connection->beginTransaction();

        $this->metrics->expects(static::once())->method('incrementGauge')
            ->with(static::equalTo('mysql_transaction_pending'));

        $this->connection->beginTransaction();
    }

    public function testCommitMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);
        $this->connection->beginTransaction();

        $this->metrics->expects(static::once())->method('decrementGauge')
            ->with(static::equalTo('mysql_transaction_pending'));

        $this->metrics->expects(static::once())->method('incrementCounter')
            ->with(static::equalTo('mysql_transaction_exec'));

        $this->connection->commit();
    }

    public function testRollbackMethodIsMeasured(): void
    {
        $this->connection->setMetricCollector($this->metrics);
        $this->connection->beginTransaction();

        $this->metrics->expects(static::once())->method('decrementGauge')
            ->with(static::equalTo('mysql_transaction_pending'));

        $this->metrics->expects(static::once())->method('incrementCounter')
            ->with(static::equalTo('mysql_transaction_exec'));

        $this->connection->rollBack();
    }
}
