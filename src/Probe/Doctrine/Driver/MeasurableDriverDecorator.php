<?php

declare(strict_types=1);

namespace Batch\Metrics\Probe\Doctrine\Driver;

use Batch\Metrics\Collector as MetricCollector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Webmozart\Assert\Assert;

/**
 * A decorator wrapping a Driver instance and intercepting method calls to measure the following metrics:
 *
 *   - mysql_connection_dial
 */
final class MeasurableDriverDecorator implements Driver, VersionAwarePlatformDriver
{
    private Driver $decoratedDriver;
    private MetricCollector $metrics;
    private string $host;

    public function __construct(Driver $decoratedDriver, MetricCollector $metrics, string $host)
    {
        $this->decoratedDriver = $decoratedDriver;
        $this->metrics = $metrics;
        $this->host = $host;
    }

    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        $start = \microtime(true);
        $result = $this->decoratedDriver->connect($params, $username, $password, $driverOptions);
        $elapsed = \microtime(true) - $start;

        $this->metrics->observeHistogram('mysql_connection_dial', $elapsed, [$this->host]);

        return $result;
    }

    public function getDatabasePlatform()
    {
        return $this->decoratedDriver->getDatabasePlatform();
    }

    public function getSchemaManager(Connection $conn)
    {
        return $this->decoratedDriver->getSchemaManager($conn);
    }

    public function getName()
    {
        return $this->decoratedDriver->getName();
    }

    public function getDatabase(Connection $conn)
    {
        return $this->decoratedDriver->getDatabase($conn);
    }

    public function createDatabasePlatformForVersion($version)
    {
        Assert::isInstanceOf($this->decoratedDriver, VersionAwarePlatformDriver::class);
        return $this->decoratedDriver->createDatabasePlatformForVersion($version);
    }
}
