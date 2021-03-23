<?php

declare(strict_types=1);

namespace App\Metric\Probe\Http;

use App\Metric\Collector as MetricCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * An event subscriber listening to "request" and "terminate" events to measure the following metrics:
 *
 *   - http_request_body_size
 *   - http_request_pending
 *   - http_request_response_time
 *   - http_request_status_code_count
 */
final class MetricSubscriber implements EventSubscriberInterface
{
    private float $requestStart = 0.0;
    private MetricCollector $metrics;

    public function __construct(MetricCollector $metrics)
    {
        $this->metrics = $metrics;
    }

    private static function isRouteBlockListed(?string $route): bool
    {
        return $route === null || \str_starts_with($route, '_') || $route === 'metrics';
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (self::isRouteBlockListed($route)) {
            return;
        }

        $this->requestStart = \microtime(true);
        $this->metrics->incrementGauge('http_request_pending', [$route]);
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (self::isRouteBlockListed($route)) {
            return;
        }

        $responseTime = \microtime(true) - $this->requestStart;
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        $bodySize = $response->headers->get('Content-Length');
        if ($bodySize !== null) {
            $bodySize = (float) $bodySize;
        } else {
            $content = $response->getContent();
            $bodySize = $content !== false ? \strlen($content) : 0;
        }

        $this->metrics->decrementGauge('http_request_pending', [$route]);
        $this->metrics->observeHistogram('http_request_response_time', $responseTime, [$route]);
        $this->metrics->incrementCounter('http_request_status_code_count', [$statusCode, $route]);
        $this->metrics->observeHistogram('http_request_body_size', $bodySize, [$route]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }
}
