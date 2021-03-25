<?php

declare(strict_types=1);

namespace Batch\Metrics;

/**
 * A class to represent an unusable Redis instance due to various issues on instanciation.
 * This class is useful when combined to the "ocramius/proxy-manager" package, see
 * the StorageAdapterFactory class.
 */
final class FailedRedis extends \Redis
{
}
