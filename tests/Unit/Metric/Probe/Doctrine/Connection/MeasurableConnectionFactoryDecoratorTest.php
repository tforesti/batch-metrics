<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Probe\Doctrine\Connection;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Doctrine\Connection\MeasurableConnection;
use App\Metric\Probe\Doctrine\Connection\MeasurableConnectionFactoryDecorator;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class MeasurableConnectionFactoryDecoratorTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    /** @var ConnectionFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $innerConnectionFactory;

    private MeasurableConnectionFactoryDecorator $connectionFactory;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);
        $this->innerConnectionFactory = $this->createMock(ConnectionFactory::class);
        $this->connectionFactory = new MeasurableConnectionFactoryDecorator(
            $this->innerConnectionFactory,
            $this->metrics
        );
    }

    public function testClassicConnectionIsCreated(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->innerConnectionFactory->method('createConnection')->willReturn($connection);

        $returnedConnection = $this->connectionFactory->createConnection([]);
        static::assertNotInstanceOf(MeasurableConnection::class, $returnedConnection);
    }

    public function testMeasuredConnectionIsCreatedAndMetricCollectorIsSet(): void
    {
        $connection = $this->createMock(MeasurableConnection::class);
        $connection->expects(static::once())->method('setMetricCollector')->with(static::equalTo($this->metrics));
        $this->innerConnectionFactory->method('createConnection')->willReturn($connection);

        $returnedConnection = $this->connectionFactory->createConnection([]);
        static::assertInstanceOf(MeasurableConnection::class, $returnedConnection);
    }
}
