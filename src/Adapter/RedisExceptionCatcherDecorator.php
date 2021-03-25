<?php

declare(strict_types=1);

namespace Batch\Metrics\Adapter;

use Batch\Metrics\Collector;

/**
 * A decorator wrapping an adapter and intercepting Redis exceptions.
 */
final class RedisExceptionCatcherDecorator implements Collector
{
    private Collector $decoratedAdapter;

    public function __construct(Collector $decoratedAdapter)
    {
        $this->decoratedAdapter = $decoratedAdapter;
    }

    private static function runWithCatchedExceptions(callable $runner): void
    {
        try {
            $runner();
        } catch (\RedisException $exception) {
            return;
        }
    }

    public function incrementCounter(string $name, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->incrementCounter($name, $labels));
    }

    public function incrementCounterBy(string $name, int $count, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->incrementCounterBy($name, $count, $labels));
    }

    public function incrementGauge(string $name, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->incrementGauge($name, $labels));
    }

    public function incrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->incrementGaugeBy($name, $value, $labels));
    }

    public function decrementGauge(string $name, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->decrementGauge($name, $labels));
    }

    public function decrementGaugeBy(string $name, float $value, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->decrementGaugeBy($name, $value, $labels));
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        self::runWithCatchedExceptions(fn() => $this->decoratedAdapter->observeHistogram($name, $value, $labels));
    }
}
