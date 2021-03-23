<?php

declare(strict_types=1);

namespace App\Tests\Unit\Metric\Probe\Redis;

use App\Metric\Collector as MetricCollector;
use App\Metric\Probe\Redis\MeasurableClientDecorator;
use PHPUnit\Framework\TestCase;

class MeasurableClientDecoratorTest extends TestCase
{
    /** @var MetricCollector|\PHPUnit\Framework\MockObject\MockObject */
    private $metrics;

    /** @var \Redis|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedRedis;

    private MeasurableClientDecorator $redisDecorator;

    public function setUp(): void
    {
        $this->metrics = $this->createMock(MetricCollector::class);
        $this->decoratedRedis = $this->createMock(\Redis::class);
        $this->redisDecorator = new MeasurableClientDecorator($this->decoratedRedis, $this->metrics);
    }

    /**
     * @dataProvider measuredMethodProvider
     */
    public function testMethodIsProxiedAndMeasured(string $method, array $args = [1, 2, 3, 4, 5]): void
    {
        $this->metrics->expects(static::once())->method('incrementCounter')
            ->with(static::equalTo('redis_operation_exec_count'));

        $this->metrics->expects(static::exactly(2))->method('observeHistogram')
            ->withConsecutive([static::equalTo('redis_operation_exec_time')], [static::equalTo('redis_value_size')]);

        $withArgs = \array_map(fn($arg) => static::equalTo($arg), $args);
        $this->decoratedRedis->expects(static::once())->method($method)->with(...$withArgs);

        // @phpstan-ignore-next-line
        $this->redisDecorator->{$method}(...$args);
    }

    /**
     * @dataProvider measuredConnectionDialMethodProvider
     */
    public function testMethodIsProxiedAndConnectionDialIsMeasured(string $method, string $expectedMethod): void
    {
        $this->metrics->expects(static::exactly(1))->method('observeHistogram')
            ->with(static::equalTo('redis_connection_dial'));

        $this->decoratedRedis->expects(static::once())->method($expectedMethod)->with(static::equalTo('localhost'));

        // @phpstan-ignore-next-line
        $this->redisDecorator->{$method}('localhost');
    }

    /**
     * @dataProvider nonMeasuredMethodProvider
     */
    public function testMethodIsProxiedButNeverMeasured(string $method, array $args = []): void
    {
        $this->metrics->expects(static::never())->method('incrementCounter');
        $this->metrics->expects(static::never())->method('incrementCounterBy');
        $this->metrics->expects(static::never())->method('incrementGauge');
        $this->metrics->expects(static::never())->method('incrementGaugeBy');
        $this->metrics->expects(static::never())->method('decrementGauge');
        $this->metrics->expects(static::never())->method('decrementGaugeBy');
        $this->metrics->expects(static::never())->method('observeHistogram');

        $withArgs = \array_map(fn($arg) => static::equalTo($arg), $args);
        $this->decoratedRedis->expects(static::once())->method($method)->with(...$withArgs);

        // @phpstan-ignore-next-line
        $this->redisDecorator->{$method}(...$args);
    }

    public function measuredMethodProvider(): \Generator
    {
        yield ['acl'];
        yield ['append'];
        yield ['bgSave'];
        yield ['bgrewriteaof'];
        yield ['bitcount'];
        yield ['bitop'];
        yield ['bitpos'];
        yield ['blPop'];
        yield ['brPop'];
        yield ['brpoplpush'];
        yield ['bzPopMax'];
        yield ['bzPopMin'];
        yield ['client'];
        yield ['command'];
        yield ['config'];
        yield ['dbSize'];
        yield ['debug'];
        yield ['decr'];
        yield ['decrBy'];
        yield ['del'];
        yield ['discard'];
        yield ['dump'];
        yield ['eval'];
        yield ['evalsha'];
        yield ['exec'];
        yield ['exists'];
        yield ['expire'];
        yield ['expireAt'];
        yield ['flushAll'];
        yield ['flushDB'];
        yield ['geoadd'];
        yield ['geodist'];
        yield ['geohash'];
        yield ['geopos'];
        yield ['georadius'];
        yield ['georadius_ro'];
        yield ['georadiusbymember', [1, 2, 3, 4]];
        yield ['georadiusbymember_ro', [1, 2, 3, 4]];
        yield ['get'];
        yield ['getBit'];
        yield ['getMode'];
        yield ['getRange'];
        yield ['getSet'];
        yield ['hDel'];
        yield ['hExists'];
        yield ['hGet'];
        yield ['hGetAll'];
        yield ['hIncrBy'];
        yield ['hIncrByFloat'];
        yield ['hKeys'];
        yield ['hLen'];
        yield ['hMget', [1, []]];
        yield ['hMset', [1, []]];
        yield ['hSet'];
        yield ['hSetNx'];
        yield ['hStrLen'];
        yield ['hVals'];
        yield ['hscan'];
        yield ['incr'];
        yield ['incrBy'];
        yield ['incrByFloat'];
        yield ['info'];
        yield ['keys'];
        yield ['lInsert'];
        yield ['lLen'];
        yield ['lPop'];
        yield ['lPush'];
        yield ['lPushx'];
        yield ['lSet'];
        yield ['lastSave'];
        yield ['lindex'];
        yield ['lrange'];
        yield ['lrem'];
        yield ['ltrim'];
        yield ['mget', [[]]];
        yield ['migrate'];
        yield ['move'];
        yield ['mset', [[]]];
        yield ['msetnx', [[]]];
        yield ['multi'];
        yield ['object'];
        yield ['persist'];
        yield ['pexpire'];
        yield ['pexpireAt'];
        yield ['pfadd', [1, []]];
        yield ['pfcount'];
        yield ['pfmerge', [1, []]];
        yield ['pipeline'];
        yield ['psetex'];
        yield ['psubscribe', [[], fn() => null]];
        yield ['pttl'];
        yield ['publish'];
        yield ['pubsub'];
        yield ['punsubscribe'];
        yield ['rPop'];
        yield ['rPush'];
        yield ['rPushx'];
        yield ['randomKey'];
        yield ['rawcommand'];
        yield ['rename'];
        yield ['renameNx'];
        yield ['restore'];
        yield ['role'];
        yield ['rpoplpush'];
        yield ['sAdd'];
        yield ['sAddArray', [1, []]];
        yield ['sDiff'];
        yield ['sDiffStore'];
        yield ['sInter'];
        yield ['sInterStore'];
        yield ['sMembers'];
        yield ['sMove'];
        yield ['sPop'];
        yield ['sRandMember'];
        yield ['sUnion'];
        yield ['sUnionStore'];
        yield ['save'];
        yield ['scan'];
        yield ['scard'];
        yield ['script'];
        yield ['set'];
        yield ['setBit'];
        yield ['setRange'];
        yield ['setex'];
        yield ['setnx'];
        yield ['sismember'];
        yield ['slaveof'];
        yield ['slowlog'];
        yield ['sort', [1]];
        yield ['sortAsc'];
        yield ['sortAscAlpha'];
        yield ['sortDesc'];
        yield ['sortDescAlpha'];
        yield ['srem'];
        yield ['sscan'];
        yield ['strlen'];
        yield ['subscribe', [[], fn() => null]];
        yield ['time'];
        yield ['ttl'];
        yield ['type'];
        yield ['unlink'];
        yield ['unsubscribe'];
        yield ['unwatch'];
        yield ['wait'];
        yield ['watch'];
        yield ['xack', [1, 2, []]];
        yield ['xadd', [1, 2, []]];
        yield ['xclaim', [1, 2, 3, 4, []]];
        yield ['xdel', [1, []]];
        yield ['xgroup'];
        yield ['xinfo'];
        yield ['xlen'];
        yield ['xpending'];
        yield ['xrange'];
        yield ['xread', [[]]];
        yield ['xreadgroup', [1, 2, []]];
        yield ['xrevrange'];
        yield ['xtrim'];
        yield ['zAdd'];
        yield ['zCard'];
        yield ['zCount'];
        yield ['zIncrBy'];
        yield ['zLexCount'];
        yield ['zPopMax'];
        yield ['zPopMin'];
        yield ['zRange'];
        yield ['zRangeByLex'];
        yield ['zRangeByScore', [1, 2, 3]];
        yield ['zRank'];
        yield ['zRem'];
        yield ['zRemRangeByLex'];
        yield ['zRemRangeByRank'];
        yield ['zRemRangeByScore'];
        yield ['zRevRange'];
        yield ['zRevRangeByLex'];
        yield ['zRevRangeByScore', [1, 2, 3]];
        yield ['zRevRank'];
        yield ['zScore'];
        yield ['zinterstore', [1, []]];
        yield ['zscan'];
        yield ['zunionstore', [1, []]];
        yield ['delete'];
        yield ['evaluate'];
        yield ['evaluateSha'];
        yield ['getKeys'];
        yield ['getMultiple', [[]]];
        yield ['lGet'];
        yield ['lGetRange'];
        yield ['lRemove'];
        yield ['lSize'];
        yield ['listTrim'];
        yield ['renameKey'];
        yield ['sContains'];
        yield ['sGetMembers'];
        yield ['sRemove'];
        yield ['sSize'];
        yield ['sendEcho'];
        yield ['setTimeout'];
        yield ['substr'];
        yield ['zDelete'];
        yield ['zDeleteRangeByRank'];
        yield ['zDeleteRangeByScore'];
        yield ['zInter', [1, []]];
        yield ['zRemove'];
        yield ['zRemoveRangeByScore'];
        yield ['zReverseRange'];
        yield ['zSize'];
        yield ['zUnion', [1, []]];
    }

    public function measuredConnectionDialMethodProvider(): \Generator
    {
        yield ['connect', 'connect'];
        yield ['pconnect', 'pconnect'];
        yield ['open', 'connect'];
        yield ['popen', 'pconnect'];
    }

    public function nonMeasuredMethodProvider(): \Generator
    {
        yield ['_prefix', [1]];
        yield ['_serialize', [1]];
        yield ['_unserialize', [1]];
        yield ['auth', [1]];
        yield ['clearLastError'];
        yield ['close'];
        yield ['echo', [1]];
        yield ['getAuth'];
        yield ['getDBNum'];
        yield ['getHost'];
        yield ['getLastError'];
        yield ['getOption', [1]];
        yield ['getPersistentID'];
        yield ['getPort'];
        yield ['getReadTimeout'];
        yield ['getTimeout'];
        yield ['isConnected'];
        yield ['ping'];
        yield ['select', [1]];
        yield ['setOption', [1, 2]];
        yield ['swapdb', [1, 2]];
    }
}
