<?php

declare(strict_types=1);

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore

namespace App\Metric\Probe\Redis;

use App\Metric\Collector as MetricCollector;
use Safe\Exceptions\JsonException;

use function Safe\json_encode;

/**
 * A decorator wrapping a Redis instance and intercepting method calls to measure the following metrics:
 *
 *   - redis_connection_dial
 *   - redis_operation_error
 *   - redis_operation_exec_count
 *   - redis_operation_exec_time
 *   - redis_value_size
 */
final class MeasurableClientDecorator extends \Redis
{
    private \Redis $decoratedClient;
    private MetricCollector $metrics;

    public function __construct(\Redis $decoratedClient, MetricCollector $metrics)
    {
        parent::__construct();

        $this->decoratedClient = $decoratedClient;
        $this->metrics = $metrics;
    }

    /** @return mixed */
    private function executeAndMeasure(string $name, array $arguments = [])
    {
        $command = \strtoupper($name);
        $start = \microtime(true);

        try {
            // @phpstan-ignore-next-line
            $result = $this->decoratedClient->{$name}(...$arguments);
        } catch (\RedisException $exception) {
            $this->metrics->incrementCounter('redis_operation_error', [$this->getHost(), $command]);
            throw $exception;
        } finally {
            $elapsed = \microtime(true) - $start;
            $this->metrics->incrementCounter('redis_operation_exec_count', [$this->getHost(), $command]);
            $this->metrics->observeHistogram('redis_operation_exec_time', $elapsed, [$this->getHost(), $command]);
        }

        try {
            $size = \strlen(json_encode($result)); // This a rough and not-really-accurate evaluation of the result size
            $this->metrics->observeHistogram('redis_value_size', $size, [$this->getHost()]);
        } catch (JsonException $exception) {
            $this->metrics->observeHistogram('redis_value_size', 0, [$this->getHost()]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function _prefix($key)
    {
        return $this->decoratedClient->_prefix($key);
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function _serialize($value)
    {
        return $this->decoratedClient->_serialize($value);
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function _unserialize($value)
    {
        return $this->decoratedClient->_unserialize($value);
    }

    public function acl($subcmd, ...$args)
    {
        return $this->executeAndMeasure('acl', \func_get_args());
    }

    public function append($key, $value)
    {
        return $this->executeAndMeasure('append', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function auth($auth)
    {
        return $this->decoratedClient->auth($auth);
    }

    public function bgSave()
    {
        return $this->executeAndMeasure('bgSave', \func_get_args());
    }

    public function bgrewriteaof()
    {
        return $this->executeAndMeasure('bgrewriteaof', \func_get_args());
    }

    public function bitcount($key)
    {
        return $this->executeAndMeasure('bitcount', \func_get_args());
    }

    public function bitop($operation, $ret_key, $key, ...$other_keys)
    {
        return $this->executeAndMeasure('bitop', \func_get_args());
    }

    public function bitpos($key, $bit, $start = null, $end = null)
    {
        return $this->executeAndMeasure('bitpos', \func_get_args());
    }

    public function blPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->executeAndMeasure('blPop', \func_get_args());
    }

    public function brPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->executeAndMeasure('brPop', \func_get_args());
    }

    public function brpoplpush($src, $dst, $timeout)
    {
        return $this->executeAndMeasure('brpoplpush', \func_get_args());
    }

    public function bzPopMax($key, $timeout_or_key, ...$extra_args)
    {
        return $this->executeAndMeasure('bzPopMax', \func_get_args());
    }

    public function bzPopMin($key, $timeout_or_key, ...$extra_args)
    {
        return $this->executeAndMeasure('bzPopMin', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function clearLastError()
    {
        return $this->decoratedClient->clearLastError();
    }

    public function client($cmd, ...$args)
    {
        return $this->executeAndMeasure('client', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function close()
    {
        return $this->decoratedClient->close();
    }

    public function command(...$args)
    {
        return $this->executeAndMeasure('command', \func_get_args());
    }

    public function config($cmd, $key, $value = null)
    {
        return $this->executeAndMeasure('config', \func_get_args());
    }

    public function connect($host, $port = null, $timeout = null, $retry_interval = null)
    {
        $start = \microtime(true);
        $success = $this->decoratedClient->connect($host, $port, $timeout, $retry_interval);
        $elapsed = \microtime(true) - $start;

        $this->metrics->observeHistogram('redis_connection_dial', $elapsed, [$host, $success]);

        return $success;
    }

    public function dbSize()
    {
        return $this->executeAndMeasure('dbSize', \func_get_args());
    }

    public function debug($key)
    {
        return $this->executeAndMeasure('debug', \func_get_args());
    }

    public function decr($key)
    {
        return $this->executeAndMeasure('decr', \func_get_args());
    }

    public function decrBy($key, $value)
    {
        return $this->executeAndMeasure('decrBy', \func_get_args());
    }

    public function del($key, ...$other_keys)
    {
        return $this->executeAndMeasure('del', \func_get_args());
    }

    public function discard()
    {
        return $this->executeAndMeasure('discard', \func_get_args());
    }

    public function dump($key)
    {
        return $this->executeAndMeasure('dump', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function echo($msg)
    {
        return $this->decoratedClient->echo($msg);
    }

    public function eval($script, $args = null, $num_keys = null)
    {
        return $this->executeAndMeasure('eval', \func_get_args());
    }

    public function evalsha($script_sha, $args = null, $num_keys = null)
    {
        return $this->executeAndMeasure('evalsha', \func_get_args());
    }

    public function exec()
    {
        return $this->executeAndMeasure('exec', \func_get_args());
    }

    public function exists($key, ...$other_keys)
    {
        return $this->executeAndMeasure('exists', \func_get_args());
    }

    public function expire($key, $timeout)
    {
        return $this->executeAndMeasure('expire', \func_get_args());
    }

    public function expireAt($key, $timestamp)
    {
        return $this->executeAndMeasure('expireAt', \func_get_args());
    }

    public function flushAll($async = null)
    {
        return $this->executeAndMeasure('flushAll', \func_get_args());
    }

    public function flushDB($async = null)
    {
        return $this->executeAndMeasure('flushDB', \func_get_args());
    }

    public function geoadd($key, $lng, $lat, $member, ...$other_triples)
    {
        return $this->executeAndMeasure('geoadd', \func_get_args());
    }

    public function geodist($key, $src, $dst, $unit = null)
    {
        return $this->executeAndMeasure('geodist', \func_get_args());
    }

    public function geohash($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('geohash', \func_get_args());
    }

    public function geopos($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('geopos', \func_get_args());
    }

    public function georadius($key, $lng, $lan, $radius, $unit, ?array $opts = null)
    {
        return $this->executeAndMeasure('georadius', \func_get_args());
    }

    public function georadius_ro($key, $lng, $lan, $radius, $unit, ?array $opts = null)
    {
        return $this->executeAndMeasure('georadius_ro', \func_get_args());
    }

    public function georadiusbymember($key, $member, $radius, $unit, ?array $opts = null)
    {
        return $this->executeAndMeasure('georadiusbymember', \func_get_args());
    }

    public function georadiusbymember_ro($key, $member, $radius, $unit, ?array $opts = null)
    {
        return $this->executeAndMeasure('georadiusbymember_ro', \func_get_args());
    }

    public function get($key)
    {
        return $this->executeAndMeasure('get', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getAuth()
    {
        return $this->decoratedClient->getAuth();
    }

    public function getBit($key, $offset)
    {
        return $this->executeAndMeasure('getBit', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getDBNum()
    {
        return $this->decoratedClient->getDBNum();
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getHost()
    {
        return $this->decoratedClient->getHost();
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getLastError()
    {
        return $this->decoratedClient->getLastError();
    }

    public function getMode()
    {
        return $this->executeAndMeasure('getMode', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getOption($option)
    {
        return $this->decoratedClient->getOption($option);
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getPersistentID()
    {
        return $this->decoratedClient->getPersistentID();
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getPort()
    {
        return $this->decoratedClient->getPort();
    }

    public function getRange($key, $start, $end)
    {
        return $this->executeAndMeasure('getRange', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getReadTimeout()
    {
        return $this->decoratedClient->getReadTimeout();
    }

    public function getSet($key, $value)
    {
        return $this->executeAndMeasure('getSet', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function getTimeout()
    {
        return $this->decoratedClient->getTimeout();
    }

    public function hDel($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('hDel', \func_get_args());
    }

    public function hExists($key, $member)
    {
        return $this->executeAndMeasure('hExists', \func_get_args());
    }

    public function hGet($key, $member)
    {
        return $this->executeAndMeasure('hGet', \func_get_args());
    }

    public function hGetAll($key)
    {
        return $this->executeAndMeasure('hGetAll', \func_get_args());
    }

    public function hIncrBy($key, $member, $value)
    {
        return $this->executeAndMeasure('hIncrBy', \func_get_args());
    }

    public function hIncrByFloat($key, $member, $value)
    {
        return $this->executeAndMeasure('hIncrByFloat', \func_get_args());
    }

    public function hKeys($key)
    {
        return $this->executeAndMeasure('hKeys', \func_get_args());
    }

    public function hLen($key)
    {
        return $this->executeAndMeasure('hLen', \func_get_args());
    }

    public function hMget($key, array $keys)
    {
        return $this->executeAndMeasure('hMget', \func_get_args());
    }

    public function hMset($key, array $pairs)
    {
        return $this->executeAndMeasure('hMset', \func_get_args());
    }

    public function hSet($key, $member, $value)
    {
        return $this->executeAndMeasure('hSet', \func_get_args());
    }

    public function hSetNx($key, $member, $value)
    {
        return $this->executeAndMeasure('hSetNx', \func_get_args());
    }

    public function hStrLen($key, $member)
    {
        return $this->executeAndMeasure('hStrLen', \func_get_args());
    }

    public function hVals($key)
    {
        return $this->executeAndMeasure('hVals', \func_get_args());
    }

    public function hscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->executeAndMeasure('hscan', \func_get_args());
    }

    public function incr($key)
    {
        return $this->executeAndMeasure('incr', \func_get_args());
    }

    public function incrBy($key, $value)
    {
        return $this->executeAndMeasure('incrBy', \func_get_args());
    }

    public function incrByFloat($key, $value)
    {
        return $this->executeAndMeasure('incrByFloat', \func_get_args());
    }

    public function info($option = null)
    {
        return $this->executeAndMeasure('info', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function isConnected()
    {
        return $this->decoratedClient->isConnected();
    }

    public function keys($pattern)
    {
        return $this->executeAndMeasure('keys', \func_get_args());
    }

    public function lInsert($key, $position, $pivot, $value)
    {
        return $this->executeAndMeasure('lInsert', \func_get_args());
    }

    public function lLen($key)
    {
        return $this->executeAndMeasure('lLen', \func_get_args());
    }

    public function lPop($key)
    {
        return $this->executeAndMeasure('lPop', \func_get_args());
    }

    public function lPush($key, $value)
    {
        return $this->executeAndMeasure('lPush', \func_get_args());
    }

    public function lPushx($key, $value)
    {
        return $this->executeAndMeasure('lPushx', \func_get_args());
    }

    public function lSet($key, $index, $value)
    {
        return $this->executeAndMeasure('lSet', \func_get_args());
    }

    public function lastSave()
    {
        return $this->executeAndMeasure('lastSave', \func_get_args());
    }

    public function lindex($key, $index)
    {
        return $this->executeAndMeasure('lindex', \func_get_args());
    }

    public function lrange($key, $start, $end)
    {
        return $this->executeAndMeasure('lrange', \func_get_args());
    }

    public function lrem($key, $value, $count)
    {
        return $this->executeAndMeasure('lrem', \func_get_args());
    }

    public function ltrim($key, $start, $stop)
    {
        return $this->executeAndMeasure('ltrim', \func_get_args());
    }

    public function mget(array $keys)
    {
        return $this->executeAndMeasure('mget', \func_get_args());
    }

    public function migrate($host, $port, $key, $db, $timeout, $copy = null, $replace = null)
    {
        return $this->executeAndMeasure('migrate', \func_get_args());
    }

    public function move($key, $dbindex)
    {
        return $this->executeAndMeasure('move', \func_get_args());
    }

    public function mset(array $pairs)
    {
        return $this->executeAndMeasure('mset', \func_get_args());
    }

    public function msetnx(array $pairs)
    {
        return $this->executeAndMeasure('msetnx', \func_get_args());
    }

    public function multi($mode = null)
    {
        return $this->executeAndMeasure('multi', \func_get_args());
    }

    public function object($field, $key)
    {
        return $this->executeAndMeasure('object', \func_get_args());
    }

    public function pconnect($host, $port = null, $timeout = null)
    {
        $start = \microtime(true);
        $success = $this->decoratedClient->pconnect($host, $port, $timeout);
        $elapsed = \microtime(true) - $start;

        $this->metrics->observeHistogram('redis_connection_dial', $elapsed, [$host, $success]);

        return $success;
    }

    public function persist($key)
    {
        return $this->executeAndMeasure('persist', \func_get_args());
    }

    public function pexpire($key, $timestamp)
    {
        return $this->executeAndMeasure('pexpire', \func_get_args());
    }

    public function pexpireAt($key, $timestamp)
    {
        return $this->executeAndMeasure('pexpireAt', \func_get_args());
    }

    public function pfadd($key, array $elements)
    {
        return $this->executeAndMeasure('pfadd', \func_get_args());
    }

    public function pfcount($key)
    {
        return $this->executeAndMeasure('pfcount', \func_get_args());
    }

    public function pfmerge($dstkey, array $keys)
    {
        return $this->executeAndMeasure('pfmerge', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function ping()
    {
        return $this->decoratedClient->ping();
    }

    public function pipeline()
    {
        return $this->executeAndMeasure('pipeline', \func_get_args());
    }

    public function psetex($key, $expire, $value)
    {
        return $this->executeAndMeasure('psetex', \func_get_args());
    }

    public function psubscribe(array $patterns, $callback)
    {
        return $this->executeAndMeasure('psubscribe', \func_get_args());
    }

    public function pttl($key)
    {
        return $this->executeAndMeasure('pttl', \func_get_args());
    }

    public function publish($channel, $message)
    {
        return $this->executeAndMeasure('publish', \func_get_args());
    }

    public function pubsub($cmd, ...$args)
    {
        return $this->executeAndMeasure('pubsub', \func_get_args());
    }

    public function punsubscribe($pattern, ...$other_patterns)
    {
        return $this->executeAndMeasure('punsubscribe', \func_get_args());
    }

    public function rPop($key)
    {
        return $this->executeAndMeasure('rPop', \func_get_args());
    }

    public function rPush($key, $value)
    {
        return $this->executeAndMeasure('rPush', \func_get_args());
    }

    public function rPushx($key, $value)
    {
        return $this->executeAndMeasure('rPushx', \func_get_args());
    }

    public function randomKey()
    {
        return $this->executeAndMeasure('randomKey', \func_get_args());
    }

    public function rawcommand($cmd, ...$args)
    {
        return $this->executeAndMeasure('rawcommand', \func_get_args());
    }

    public function rename($key, $newkey)
    {
        return $this->executeAndMeasure('rename', \func_get_args());
    }

    public function renameNx($key, $newkey)
    {
        return $this->executeAndMeasure('renameNx', \func_get_args());
    }

    public function restore($ttl, $key, $value)
    {
        return $this->executeAndMeasure('restore', \func_get_args());
    }

    public function role()
    {
        return $this->executeAndMeasure('role', \func_get_args());
    }

    public function rpoplpush($src, $dst)
    {
        return $this->executeAndMeasure('rpoplpush', \func_get_args());
    }

    public function sAdd($key, $value)
    {
        return $this->executeAndMeasure('sAdd', \func_get_args());
    }

    public function sAddArray($key, array $options)
    {
        return $this->executeAndMeasure('sAddArray', \func_get_args());
    }

    public function sDiff($key, ...$other_keys)
    {
        return $this->executeAndMeasure('sDiff', \func_get_args());
    }

    public function sDiffStore($dst, $key, ...$other_keys)
    {
        return $this->executeAndMeasure('sDiffStore', \func_get_args());
    }

    public function sInter($key, ...$other_keys)
    {
        return $this->executeAndMeasure('sInter', \func_get_args());
    }

    public function sInterStore($dst, $key, ...$other_keys)
    {
        return $this->executeAndMeasure('sInterStore', \func_get_args());
    }

    public function sMembers($key)
    {
        return $this->executeAndMeasure('sMembers', \func_get_args());
    }

    public function sMove($src, $dst, $value)
    {
        return $this->executeAndMeasure('sMove', \func_get_args());
    }

    public function sPop($key)
    {
        return $this->executeAndMeasure('sPop', \func_get_args());
    }

    public function sRandMember($key, $count = null)
    {
        return $this->executeAndMeasure('sRandMember', \func_get_args());
    }

    public function sUnion($key, ...$other_keys)
    {
        return $this->executeAndMeasure('sUnion', \func_get_args());
    }

    public function sUnionStore($dst, $key, ...$other_keys)
    {
        return $this->executeAndMeasure('sUnionStore', \func_get_args());
    }

    public function save()
    {
        return $this->executeAndMeasure('save', \func_get_args());
    }

    public function scan(&$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->executeAndMeasure('scan', \func_get_args());
    }

    public function scard($key)
    {
        return $this->executeAndMeasure('scard', \func_get_args());
    }

    public function script($cmd, ...$args)
    {
        return $this->executeAndMeasure('script', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function select($dbindex)
    {
        return $this->decoratedClient->select($dbindex);
    }

    public function set($key, $value, $opts = null)
    {
        return $this->executeAndMeasure('set', \func_get_args());
    }

    public function setBit($key, $offset, $value)
    {
        return $this->executeAndMeasure('setBit', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function setOption($option, $value)
    {
        return $this->decoratedClient->setOption($option, $value);
    }

    public function setRange($key, $offset, $value)
    {
        return $this->executeAndMeasure('setRange', \func_get_args());
    }

    public function setex($key, $expire, $value)
    {
        return $this->executeAndMeasure('setex', \func_get_args());
    }

    public function setnx($key, $value)
    {
        return $this->executeAndMeasure('setnx', \func_get_args());
    }

    public function sismember($key, $value)
    {
        return $this->executeAndMeasure('sismember', \func_get_args());
    }

    public function slaveof($host = null, $port = null)
    {
        return $this->executeAndMeasure('slaveof', \func_get_args());
    }

    public function slowlog($arg, $option = null)
    {
        return $this->executeAndMeasure('slowlog', \func_get_args());
    }

    public function sort($key, ?array $options = null)
    {
        return $this->executeAndMeasure('sort', \func_get_args());
    }

    public function sortAsc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->executeAndMeasure('sortAsc', \func_get_args());
    }

    public function sortAscAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->executeAndMeasure('sortAscAlpha', \func_get_args());
    }

    public function sortDesc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->executeAndMeasure('sortDesc', \func_get_args());
    }

    public function sortDescAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->executeAndMeasure('sortDescAlpha', \func_get_args());
    }

    public function srem($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('srem', \func_get_args());
    }

    public function sscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->executeAndMeasure('sscan', \func_get_args());
    }

    public function strlen($key)
    {
        return $this->executeAndMeasure('strlen', \func_get_args());
    }

    public function subscribe(array $channels, $callback)
    {
        return $this->executeAndMeasure('subscribe', \func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * This method is NOT measured because it would be irrelevant.
     */
    public function swapdb($srcdb, $dstdb)
    {
        return $this->decoratedClient->swapdb($srcdb, $dstdb);
    }

    public function time()
    {
        return $this->executeAndMeasure('time', \func_get_args());
    }

    public function ttl($key)
    {
        return $this->executeAndMeasure('ttl', \func_get_args());
    }

    public function type($key)
    {
        return $this->executeAndMeasure('type', \func_get_args());
    }

    public function unlink($key, ...$other_keys)
    {
        return $this->executeAndMeasure('unlink', \func_get_args());
    }

    public function unsubscribe($channel, ...$other_channels)
    {
        return $this->executeAndMeasure('unsubscribe', \func_get_args());
    }

    public function unwatch()
    {
        return $this->executeAndMeasure('unwatch', \func_get_args());
    }

    public function wait($numslaves, $timeout)
    {
        return $this->executeAndMeasure('wait', \func_get_args());
    }

    public function watch($key, ...$other_keys)
    {
        return $this->executeAndMeasure('watch', \func_get_args());
    }

    public function xack($str_key, $str_group, array $arr_ids)
    {
        return $this->executeAndMeasure('xack', \func_get_args());
    }

    public function xadd($str_key, $str_id, array $arr_fields, $i_maxlen = null, $boo_approximate = null)
    {
        return $this->executeAndMeasure('xadd', \func_get_args());
    }

    public function xclaim($str_key, $str_group, $str_consumer, $i_min_idle, array $arr_ids, ?array $arr_opts = null)
    {
        return $this->executeAndMeasure('xclaim', \func_get_args());
    }

    public function xdel($str_key, array $arr_ids)
    {
        return $this->executeAndMeasure('xdel', \func_get_args());
    }

    public function xgroup($str_operation, $str_key = null, $str_arg1 = null, $str_arg2 = null, $str_arg3 = null)
    {
        return $this->executeAndMeasure('xgroup', \func_get_args());
    }

    public function xinfo($str_cmd, $str_key = null, $str_group = null)
    {
        return $this->executeAndMeasure('xinfo', \func_get_args());
    }

    public function xlen($key)
    {
        return $this->executeAndMeasure('xlen', \func_get_args());
    }

    public function xpending(
        $str_key,
        $str_group,
        $str_start = null,
        $str_end = null,
        $i_count = null,
        $str_consumer = null
    ) {
        return $this->executeAndMeasure('xpending', \func_get_args());
    }

    public function xrange($str_key, $str_start, $str_end, $i_count = null)
    {
        return $this->executeAndMeasure('xrange', \func_get_args());
    }

    public function xread(array $arr_streams, $i_count = null, $i_block = null)
    {
        return $this->executeAndMeasure('xread', \func_get_args());
    }

    public function xreadgroup($str_group, $str_consumer, array $arr_streams, $i_count = null, $i_block = null)
    {
        return $this->executeAndMeasure('xreadgroup', \func_get_args());
    }

    public function xrevrange($str_key, $str_start, $str_end, $i_count = null)
    {
        return $this->executeAndMeasure('xrevrange', \func_get_args());
    }

    public function xtrim($str_key, $i_maxlen, $boo_approximate = null)
    {
        return $this->executeAndMeasure('xtrim', \func_get_args());
    }

    public function zAdd($key, $score, $value, ...$extra_args)
    {
        return $this->executeAndMeasure('zAdd', \func_get_args());
    }

    public function zCard($key)
    {
        return $this->executeAndMeasure('zCard', \func_get_args());
    }

    public function zCount($key, $min, $max)
    {
        return $this->executeAndMeasure('zCount', \func_get_args());
    }

    public function zIncrBy($key, $value, $member)
    {
        return $this->executeAndMeasure('zIncrBy', \func_get_args());
    }

    public function zLexCount($key, $min, $max)
    {
        return $this->executeAndMeasure('zLexCount', \func_get_args());
    }

    public function zPopMax($key)
    {
        return $this->executeAndMeasure('zPopMax', \func_get_args());
    }

    public function zPopMin($key)
    {
        return $this->executeAndMeasure('zPopMin', \func_get_args());
    }

    public function zRange($key, $start, $end, $scores = null)
    {
        return $this->executeAndMeasure('zRange', \func_get_args());
    }

    public function zRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->executeAndMeasure('zRangeByLex', \func_get_args());
    }

    public function zRangeByScore($key, $start, $end, ?array $options = null)
    {
        return $this->executeAndMeasure('zRangeByScore', \func_get_args());
    }

    public function zRank($key, $member)
    {
        return $this->executeAndMeasure('zRank', \func_get_args());
    }

    public function zRem($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('zRem', \func_get_args());
    }

    public function zRemRangeByLex($key, $min, $max)
    {
        return $this->executeAndMeasure('zRemRangeByLex', \func_get_args());
    }

    public function zRemRangeByRank($key, $start, $end)
    {
        return $this->executeAndMeasure('zRemRangeByRank', \func_get_args());
    }

    public function zRemRangeByScore($key, $min, $max)
    {
        return $this->executeAndMeasure('zRemRangeByScore', \func_get_args());
    }

    public function zRevRange($key, $start, $end, $scores = null)
    {
        return $this->executeAndMeasure('zRevRange', \func_get_args());
    }

    public function zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->executeAndMeasure('zRevRangeByLex', \func_get_args());
    }

    public function zRevRangeByScore($key, $start, $end, ?array $options = null)
    {
        return $this->executeAndMeasure('zRevRangeByScore', \func_get_args());
    }

    public function zRevRank($key, $member)
    {
        return $this->executeAndMeasure('zRevRank', \func_get_args());
    }

    public function zScore($key, $member)
    {
        return $this->executeAndMeasure('zScore', \func_get_args());
    }

    public function zinterstore($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->executeAndMeasure('zinterstore', \func_get_args());
    }

    public function zscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->executeAndMeasure('zscan', \func_get_args());
    }

    public function zunionstore($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->executeAndMeasure('zunionstore', \func_get_args());
    }

    public function delete($key, ...$other_keys)
    {
        return $this->executeAndMeasure('delete', \func_get_args());
    }

    public function evaluate($script, $args = null, $num_keys = null)
    {
        return $this->executeAndMeasure('evaluate', \func_get_args());
    }

    public function evaluateSha($script_sha, $args = null, $num_keys = null)
    {
        return $this->executeAndMeasure('evaluateSha', \func_get_args());
    }

    public function getKeys($pattern)
    {
        return $this->executeAndMeasure('getKeys', \func_get_args());
    }

    public function getMultiple(array $keys)
    {
        return $this->executeAndMeasure('getMultiple', \func_get_args());
    }

    public function lGet($key, $index)
    {
        return $this->executeAndMeasure('lGet', \func_get_args());
    }

    public function lGetRange($key, $start, $end)
    {
        return $this->executeAndMeasure('lGetRange', \func_get_args());
    }

    public function lRemove($key, $value, $count)
    {
        return $this->executeAndMeasure('lRemove', \func_get_args());
    }

    public function lSize($key)
    {
        return $this->executeAndMeasure('lSize', \func_get_args());
    }

    public function listTrim($key, $start, $stop)
    {
        return $this->executeAndMeasure('listTrim', \func_get_args());
    }

    public function open($host, $port = null, $timeout = null, $retry_interval = null)
    {
        return $this->connect($host, $port, $timeout, $retry_interval);
    }

    public function popen($host, $port = null, $timeout = null)
    {
        return $this->pconnect($host, $port, $timeout);
    }

    public function renameKey($key, $newkey)
    {
        return $this->executeAndMeasure('renameKey', \func_get_args());
    }

    public function sContains($key, $value)
    {
        return $this->executeAndMeasure('sContains', \func_get_args());
    }

    public function sGetMembers($key)
    {
        return $this->executeAndMeasure('sGetMembers', \func_get_args());
    }

    public function sRemove($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('sRemove', \func_get_args());
    }

    public function sSize($key)
    {
        return $this->executeAndMeasure('sSize', \func_get_args());
    }

    public function sendEcho($msg)
    {
        return $this->executeAndMeasure('sendEcho', \func_get_args());
    }

    public function setTimeout($key, $timeout)
    {
        return $this->executeAndMeasure('setTimeout', \func_get_args());
    }

    public function substr($key, $start, $end)
    {
        return $this->executeAndMeasure('substr', \func_get_args());
    }

    public function zDelete($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('zDelete', \func_get_args());
    }

    public function zDeleteRangeByRank($key, $min, $max)
    {
        return $this->executeAndMeasure('zDeleteRangeByRank', \func_get_args());
    }

    public function zDeleteRangeByScore($key, $min, $max)
    {
        return $this->executeAndMeasure('zDeleteRangeByScore', \func_get_args());
    }

    public function zInter($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->executeAndMeasure('zInter', \func_get_args());
    }

    public function zRemove($key, $member, ...$other_members)
    {
        return $this->executeAndMeasure('zRemove', \func_get_args());
    }

    public function zRemoveRangeByScore($key, $min, $max)
    {
        return $this->executeAndMeasure('zRemoveRangeByScore', \func_get_args());
    }

    public function zReverseRange($key, $start, $end, $scores = null)
    {
        return $this->executeAndMeasure('zReverseRange', \func_get_args());
    }

    public function zSize($key)
    {
        return $this->executeAndMeasure('zSize', \func_get_args());
    }

    public function zUnion($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->executeAndMeasure('zUnion', \func_get_args());
    }
}
