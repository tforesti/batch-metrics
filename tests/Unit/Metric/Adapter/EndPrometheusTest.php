<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Adapter;

use App\Metric\Adapter\EndPrometheus;
use App\Metric\Collector as MetricCollector;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;

class EndPrometheusTest extends TestCase
{
    /** @var CollectorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $endPrometheus;

    private MetricCollector $adapter;

    public function setUp(): void
    {
        $this->endPrometheus = $this->createMock(CollectorRegistry::class);
        $this->adapter = new EndPrometheus($this->endPrometheus);
    }

    public function testNameSplitWithIncrementCounter(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getCounter')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->incrementCounter('namespace_name');
    }

    public function testNameSplitWithIncrementCounterBy(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getCounter')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->incrementCounterBy('namespace_name', 1);
    }

    public function testNameSplitWithIncrementGauge(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getGauge')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->incrementGauge('namespace_name');
    }

    public function testNameSplitWithIncrementGaugeBy(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getGauge')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->incrementGaugeBy('namespace_name', 1);
    }

    public function testNameSplitWithDecrementGauge(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getGauge')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->decrementGauge('namespace_name');
    }

    public function testNameSplitWithDecrementGaugeBy(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getGauge')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->incrementGaugeBy('namespace_name', 1);
    }

    public function testNameSplitWithObserveHistogram(): void
    {
        $this->endPrometheus
            ->expects(static::once())
            ->method('getHistogram')
            ->with(static::equalTo('namespace'), static::equalTo('name'));

        $this->adapter->observeHistogram('namespace_name', 1);
    }
}
