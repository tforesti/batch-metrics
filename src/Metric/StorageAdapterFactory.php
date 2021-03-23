<?php

declare(strict_types=1);

namespace App\Metric;

use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory as InMemoryStorage;
use Prometheus\Storage\Redis as RedisStorage;
use ProxyManager\Proxy\VirtualProxyInterface;

/**
 * A factory to return InMemoryStorage instead of RedisStorage when the Redis instance is not
 * defined (which happens when the creation fails in FailableRedisFactoryDecorator).
 */
final class StorageAdapterFactory
{
    public function create(\Redis $redis): Adapter
    {
        if ($redis instanceof VirtualProxyInterface && !$redis->isProxyInitialized()) {
            $redis->initializeProxy();
            $newRedis = $redis->getWrappedValueHolderValue();
            $redis = $newRedis instanceof \Redis ? $newRedis : $redis;
        }

        return $redis instanceof FailedRedis
            ? new InMemoryStorage()
            : RedisStorage::fromExistingConnection($redis);
    }
}
