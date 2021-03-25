<?php

declare(strict_types=1);

namespace Batch\Metrics\Adapter;

use Batch\Metrics\Collector;
use Prometheus\CollectorRegistry;

/**
 * An adapter for the endclothing/prometheus_client_php package.
 */
final class EndPrometheus implements Collector
{
    private CollectorRegistry $adaptedCollector;

    public function __construct(CollectorRegistry $adaptedCollector)
    {
        $this->adaptedCollector = $adaptedCollector;
    }

    /** @return array<int, string> */
    private static function splitName(string $name): array
    {
        if (\str_contains($name, '_')) {
            return \explode('_', $name, 2);
        }

        return ['', $name];
    }

    public function incrementCounter(string $name, array $labels = []): void
    {
        $this->adaptedCollector->getCounter(...self::splitName($name))->inc($labels);
    }

    public function incrementCounterBy(string $name, int $count, array $labels = []): void
    {
        $this->adaptedCollector->getCounter(...self::splitName($name))->incBy($count, $labels);
    }

    public function incrementGauge(string $name, array $labels = []): void
    {
        $this->adaptedCollector->getGauge(...self::splitName($name))->inc($labels);
    }

    public function incrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        $this->adaptedCollector->getGauge(...self::splitName($name))->incBy($value, $labels);
    }

    public function decrementGauge(string $name, array $labels = []): void
    {
        $this->adaptedCollector->getGauge(...self::splitName($name))->dec($labels);
    }

    public function decrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        $this->adaptedCollector->getGauge(...self::splitName($name))->decBy($value, $labels);
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $this->adaptedCollector->getHistogram(...self::splitName($name))->observe($value, $labels);
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->adaptedCollector;
    }
}
