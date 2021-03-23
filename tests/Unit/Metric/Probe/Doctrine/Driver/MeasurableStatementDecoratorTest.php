<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Probe\Doctrine\Driver;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Doctrine\Driver\MeasurableStatementDecorator;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\TestCase;

class MeasurableStatementDecoratorTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    private MeasurableStatementDecorator $statement;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);

        /** @var (Statement&Result)|\PHPUnit\Framework\MockObject\MockObject */
        $originalStatement = $this->createMock(Statement::class);

        $this->statement = new MeasurableStatementDecorator(
            $originalStatement,
            $this->metrics,
            'SELECT',
            'localhost'
        );
    }

    public function testExecuteMethodIsMeasured(): void
    {
        $this->metrics->expects(static::once())->method('observeHistogram')
            ->with(static::equalTo('mysql_query_execution_time'));

        $this->statement->execute();
    }
}
