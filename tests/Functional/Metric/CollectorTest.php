<?php

declare(strict_types=1);

namespace App\Tests\Functional\Metric;

use App\Metric\Collector as MetricCollector;
use Prometheus\CollectorRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CollectorTest extends KernelTestCase
{
    private MetricCollector $metrics;
    private CollectorRegistry $registry;

    public function setUp(): void
    {
        static::bootKernel();

        $this->metrics = static::$container->get(MetricCollector::class);
        $this->registry = static::$container->get(CollectorRegistry::class);
    }

    public function testStoringAndRetrievingMetrics(): void
    {
        $this->registry->registerCounter('', 'counter', '', []);
        static::assertCount(0, $this->registry->getMetricFamilySamples());
        $this->metrics->incrementCounter('counter');
        static::assertNotCount(0, $this->registry->getMetricFamilySamples());
    }
}
