<?php

declare(strict_types=1);

namespace Batch\Metrics\Probe\Doctrine\Connection;

use Batch\Metrics\Collector as MetricCollector;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

/**
 * A decorator wrapping a ConnectionFactory instance and defining a metric collector on the created connections.
 */
final class MeasurableConnectionFactoryDecorator extends ConnectionFactory
{
    private ConnectionFactory $decoratedFactory;
    private MetricCollector $metrics;

    public function __construct(ConnectionFactory $decoratedFactory, MetricCollector $metrics)
    {
        parent::__construct([]);

        $this->decoratedFactory = $decoratedFactory;
        $this->metrics = $metrics;
    }

    /**
     * Create a connection by name.
     *
     * @param mixed[] $params
     * @param string[]|Type[] $mappingTypes
     *
     * @return Connection
     */
    public function createConnection(
        array $params,
        ?Configuration $config = null,
        ?EventManager $eventManager = null,
        array $mappingTypes = []
    ) {
        $connection = $this->decoratedFactory->createConnection($params, $config, $eventManager, $mappingTypes);

        if ($connection instanceof MeasurableConnection) {
            $connection->setMetricCollector($this->metrics);
        }

        return $connection;
    }
}
