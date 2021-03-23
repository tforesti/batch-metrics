<?php

declare(strict_types=1);

namespace App\Metric\Adapter;

use App\Metric\Collector;
use Psr\Log\LoggerInterface;

/**
 * A decorator wrapping an adapter and intercepting method calls to log the metrics. Use for debugging purpose.
 */
final class LoggerDecorator implements Collector
{
    private Collector $decoratedAdapter;
    private LoggerInterface $logger;

    public function __construct(Collector $decoratedAdapter, LoggerInterface $logger)
    {
        $this->decoratedAdapter = $decoratedAdapter;
        $this->logger = $logger;
    }

    public function incrementCounter(string $name, array $labels = []): void
    {
        $this->logger->debug(
            "Counter $name incremented by \"1\".",
            ['type' => 'counter', 'metric' => $name, 'count' => 1, 'labels' => $labels]
        );

        $this->decoratedAdapter->incrementCounter($name, $labels);
    }

    public function incrementCounterBy(string $name, int $count, array $labels = []): void
    {
        $this->logger->debug(
            "Counter $name incremented by \"$count\".",
            ['type' => 'counter', 'metric' => $name, 'count' => $count, 'labels' => $labels]
        );

        $this->decoratedAdapter->incrementCounterBy($name, $count, $labels);
    }

    public function incrementGauge(string $name, array $labels = []): void
    {
        $this->logger->debug(
            "Gauge $name incremented by \"1\".",
            ['type' => 'gauge', 'metric' => $name, 'count' => 1, 'labels' => $labels]
        );

        $this->decoratedAdapter->incrementGauge($name, $labels);
    }

    public function incrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        $this->logger->debug(
            "Gauge $name incremented by \"$value\".",
            ['type' => 'gauge', 'metric' => $name, 'value' => $value, 'labels' => $labels]
        );

        $this->decoratedAdapter->incrementGaugeBy($name, $value, $labels);
    }

    public function decrementGauge(string $name, array $labels = []): void
    {
        $this->logger->debug(
            "Gauge $name decremented by \"1\".",
            ['type' => 'gauge', 'metric' => $name, 'count' => 1, 'labels' => $labels]
        );

        $this->decoratedAdapter->decrementGauge($name, $labels);
    }

    public function decrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        $this->logger->debug(
            "Gauge $name decremented by \"$value\".",
            ['type' => 'gauge', 'metric' => $name, 'value' => $value, 'labels' => $labels]
        );

        $this->decoratedAdapter->decrementGaugeBy($name, $value, $labels);
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $this->logger->debug(
            "Histogram $name updated with \"$value\".",
            ['type' => 'histogram', 'metric' => $name, 'value' => $value, 'labels' => $labels]
        );

        $this->decoratedAdapter->observeHistogram($name, $value, $labels);
    }
}
