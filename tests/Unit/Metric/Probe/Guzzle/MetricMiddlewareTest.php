<?php

declare(strict_types=1);

namespace Batch\Metrics\Tests\Unit\Metric\Probe\Guzzle;

use Batch\Metrics\Collector as MetricCollector;
use Batch\Metrics\Probe\Guzzle\MetricMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class MetricMiddlewareTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    private Client $guzzleClient;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);
        $metricMiddleware = new MetricMiddleware($this->metrics);

        $handlerStack = new HandlerStack(new MockHandler([new Response()]));
        $handlerStack->push($metricMiddleware());

        $this->guzzleClient = new Client(['handler' => $handlerStack]);
    }

    public function testRequestIsHandledAndResponseIsMeasured(): void
    {
        $this->metrics->expects(static::once())->method('incrementGauge')
            ->with(static::equalTo('api_request_pending'));

        $this->metrics->expects(static::once())->method('decrementGauge')
            ->with(static::equalTo('api_request_pending'));

        $this->metrics->expects(static::exactly(2))->method('observeHistogram')
            ->withConsecutive(
                [static::equalTo('api_request_response_time')],
                [static::equalTo('api_request_body_size')]
            );

        $this->metrics->expects(static::once())->method('incrementCounter')
            ->with(static::equalTo('api_request_status_code_count'));

        $this->guzzleClient->get('http://localhost');
    }
}
