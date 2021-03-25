<?php

declare(strict_types=1);

namespace Batch\Metrics\Probe\Guzzle;

use Batch\Metrics\Collector as MetricCollector;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A Guzzle middleware intercepting requests and responses to measure the following metrics (where "api" is a
 * customisable prefix):
 *
 *   - api_request_body_size
 *   - api_request_pending
 *   - api_request_response_time
 *   - api_request_status_code_count
 */
final class MetricMiddleware
{
    private MetricCollector $metrics;

    public function __construct(MetricCollector $metrics)
    {
        $this->metrics = $metrics;
    }

    public function __invoke(string $metricPrefix = 'api'): callable
    {
        return function (callable $handler) use ($metricPrefix): callable {
            return function (RequestInterface $request, array $options) use ($metricPrefix, $handler) {
                $requestStart = \microtime(true);
                $this->metrics->incrementGauge("{$metricPrefix}_request_pending");

                $promise = $handler($request, $options);

                return $promise->then(
                    function (ResponseInterface $response) use ($metricPrefix, $requestStart): ResponseInterface {
                        $responseTime = \microtime(true) - $requestStart;
                        $statusCode = $response->getStatusCode();

                        $bodySize = $response->getHeader('Content-Length')[0] ?? null;
                        if ($bodySize !== null) {
                            $bodySize = (float) $bodySize;
                        } else {
                            $bodySize = \strlen($response->getBody()->getContents());
                        }

                        $this->metrics->decrementGauge("{$metricPrefix}_request_pending");
                        $this->metrics->observeHistogram("{$metricPrefix}_request_response_time", $responseTime);
                        $this->metrics->incrementCounter("{$metricPrefix}_request_status_code_count", [$statusCode]);
                        $this->metrics->observeHistogram("{$metricPrefix}_request_body_size", $bodySize);

                        return $response;
                    }
                );
            };
        };
    }
}
