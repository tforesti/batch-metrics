<?php

declare(strict_types=1);

namespace App\Metric;

interface Collector
{
    public function incrementCounter(string $name, array $labels = []): void;

    public function incrementCounterBy(string $name, int $count, array $labels = []): void;

    public function incrementGauge(string $name, array $labels = []): void;

    public function incrementGaugeBy(string $name, float $value, array $labels = []): void;

    public function decrementGauge(string $name, array $labels = []): void;

    public function decrementGaugeBy(string $name, float $value, array $labels = []): void;

    public function observeHistogram(string $name, float $value, array $labels = []): void;
}
