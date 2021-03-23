<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Adapter;

use App\Metric\Adapter\RedisExceptionCatcherDecorator;
use App\Metric\Collector as MetricCollector;
use PHPUnit\Framework\TestCase;

class RedisExceptionCatcherDecoratorTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedAdapter;

    private MetricCollector $adapter;

    public function setUp(): void
    {
        $this->decoratedAdapter = $this->createMock(MetricCollector::class);
        $this->adapter = new RedisExceptionCatcherDecorator($this->decoratedAdapter);
    }

    /**
     * @param int|string $args
     * @dataProvider methodProvider
     */
    public function testRedisExceptionsAreCatched(string $method, ...$args): void
    {
        $this->decoratedAdapter->method($method)->willThrowException(new \RedisException());

        // @phpstan-ignore-next-line
        $result = $this->adapter->{$method}(...$args);

        static::assertNull($result);
    }

    /**
     * @param int|string $args
     * @dataProvider methodProvider
     */
    public function testOtherExceptionsAreNotCatched(string $method, ...$args): void
    {
        $this->decoratedAdapter->method($method)->willThrowException(new \Exception());

        $this->expectException(\Exception::class);

        // @phpstan-ignore-next-line
        $this->adapter->{$method}(...$args);
    }

    public function methodProvider(): \Generator
    {
        yield ['incrementCounter', 'metric_name'];
        yield ['incrementCounterBy', 'metric_name', 0];
        yield ['incrementGauge', 'metric_name'];
        yield ['incrementGaugeBy', 'metric_name', 0];
        yield ['decrementGauge', 'metric_name'];
        yield ['decrementGaugeBy', 'metric_name', 0];
        yield ['observeHistogram', 'metric_name', 0];
    }
}
