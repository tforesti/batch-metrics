<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Probe\Http;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Http\MetricSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class MetricSubscriberTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    private MetricSubscriber $httpSubscriber;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);
        $this->httpSubscriber = new MetricSubscriber($this->metrics);
    }

    public function testRequestIsHandledAndResponseIsMeasured(): void
    {
        $this->metrics->expects(static::once())->method('incrementGauge')
            ->with(static::equalTo('http_request_pending'));

        $kernel = $this->createMock(KernelInterface::class);
        $request = new Request([], [], ['_route' => 'test']);
        $event = new RequestEvent($kernel, $request, null);
        $this->httpSubscriber->onKernelRequest($event);

        $this->metrics->expects(static::once())->method('decrementGauge')
            ->with(static::equalTo('http_request_pending'));

        $this->metrics->expects(static::exactly(2))->method('observeHistogram')
            ->withConsecutive(
                [static::equalTo('http_request_response_time')],
                [static::equalTo('http_request_body_size')]
            );

        $this->metrics->expects(static::once())->method('incrementCounter')
            ->with(static::equalTo('http_request_status_code_count'));

        $response = new Response('This is the response content');
        $event = new TerminateEvent($kernel, $request, $response);
        $this->httpSubscriber->onKernelTerminate($event);
    }
}
