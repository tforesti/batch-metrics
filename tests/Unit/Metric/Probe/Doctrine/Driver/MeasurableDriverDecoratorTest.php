<?php

declare(strict_types=1);

namespace Batch\Metrics\Tests\Unit\Metric\Probe\Doctrine\Driver;

use Batch\Metrics\Collector as MetricCollector;
use Batch\Metrics\Probe\Doctrine\Driver\MeasurableDriverDecorator;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use PHPUnit\Framework\TestCase;

class MeasurableDriverDecoratorTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    private MeasurableDriverDecorator $driver;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);
        $this->driver = new MeasurableDriverDecorator($this->createMock(Driver::class), $this->metrics, 'localhost');
    }

    public function testConnectMethodIsMeasured(): void
    {
        $this->metrics->expects(static::once())->method('observeHistogram')
            ->with(static::equalTo('mysql_connection_dial'));

        $this->driver->connect([]);
    }
}
