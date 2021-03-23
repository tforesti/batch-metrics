<?php

declare(strict_types=1);

namespace App\Metric;

use Snc\RedisBundle\Client\Phpredis\Client;
use Snc\RedisBundle\Client\Phpredis\ClientCluster;
use Snc\RedisBundle\Factory\PhpredisClientFactory;

/**
 * A decorator wrapping a PhpredisClientFactory instance and returning a
 * `FailedRedis` instance on a failed creation (e.g. a failed connection).
 */
final class FailableRedisFactoryDecorator
{
    private PhpredisClientFactory $decoratedFactory;

    public function __construct(PhpredisClientFactory $decoratedFactory)
    {
        $this->decoratedFactory = $decoratedFactory;
    }

    /**
     * @return \Redis|Client|\RedisCluster|ClientCluster
     */
    public function create(string $class, array $dsns, array $options, string $alias)
    {
        try {
            return $this->decoratedFactory->create($class, $dsns, $options, $alias);
        } catch (\RedisException $exception) {
            return new FailedRedis();
        }
    }
}
