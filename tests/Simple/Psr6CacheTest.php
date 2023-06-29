<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Cache\Test\Simple;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Cache\StorageCacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Filesystem\Dir;
use Psr\SimpleCache\CacheInterface;
use Psr\Clock\ClockInterface;
use Tobento\Service\Iterable\Iter;
use DateInterval;

/**
 * Psr6CacheTest
 */
class Psr6CacheTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }
    
    protected function createCache(
        string $namespace = 'ns',
        null|ClockInterface $clock = null,
        null|int $ttl = null,
        null|StorageInterface $storage = null,
    ): Psr6Cache {
        
        if (is_null($storage)) {
            $tables = new Tables();
            $tables->add('cache_items', ['id', 'data', 'expiration', 'namespace'], 'id');
            
            $storage = new JsonFileStorage(
                dir: __DIR__.'/tmp/',
                tables: $tables
            );
        }
        
        if (is_null($clock)) {
            $clock = new FrozenClock();
        }
        
        $pool = new StorageCacheItemPool(
            storage: $storage,
            namespace: $namespace,
            clock: $clock,
            ttl: $ttl,
            table: 'cache_items',
            idCol: 'id',
            dataCol: 'data',
            expirationCol: 'expiration',
            namespaceCol: 'namespace',
        );
        
        return new Psr6Cache(
            pool: $pool,
            namespace: $namespace,
            ttl: $ttl,
        );
    }
    
    public function testInterfaces()
    {
        $cache = $this->createCache();
        
        $this->assertInstanceof(CacheInterface::class, $cache);
        $this->assertInstanceof(CanDeleteExpiredItems::class, $cache);
    }

    public function testGetMethodIfMissReturnsDefault()
    {
        $cache = $this->createCache();
        
        $this->assertSame(null, $cache->get('foo'));
        $this->assertSame('default', $cache->get('foo', 'default'));
    }
    
    public function testGetMethodReturnsValueFromCache()
    {
        $cache = $this->createCache();
        $cache->set('foo', 'value');
        
        $this->assertSame('value', $cache->get('foo'));
        $this->assertSame('value', $cache->get('foo', 'default'));
    }
    
    public function testSetMethodWithTtlNull()
    {
        $cache = $this->createCache();
        $cache->set(key: 'foo', value: 'value', ttl: null);
        
        $this->assertSame('value', $cache->get('foo'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+100 years'),
        );
        
        $this->assertSame('value', $cache->get('foo'));
    }
    
    public function testSetMethodWithTtlSeconds()
    {
        $cache = $this->createCache();
        $cache->set(key: 'foo', value: 'value', ttl: 30);
        
        $this->assertSame('value', $cache->get('foo'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+29 seconds'),
        );
        
        $this->assertSame('value', $cache->get('foo'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+31 seconds'),
        );
        
        $this->assertSame(null, $cache->get('foo'));
    }
    
    public function testSetMethodWithTtlDateInterval()
    {
        $cache = $this->createCache();
        $cache->set(key: 'foo', value: 'value', ttl: new DateInterval('PT30S'));
        
        $this->assertSame('value', $cache->get('foo'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+29 seconds'),
        );
        
        $this->assertSame('value', $cache->get('foo'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+31 seconds'),
        );
        
        $this->assertSame(null, $cache->get('foo'));
    }
    
    public function testDeleteMethod()
    {
        $cache = $this->createCache();
        $this->assertTrue($cache->delete('foo'));
        
        $cache->set(key: 'foo', value: 'value');
        $this->assertSame('value', $cache->get('foo'));
        $this->assertTrue($cache->delete('foo'));
        $this->assertSame(null, $cache->get('foo'));
    }
    
    public function testClearMethod()
    {
        $cache = $this->createCache();        
        $cache->set(key: 'foo', value: 'value');
        $this->assertSame('value', $cache->get('foo'));
        $this->assertTrue($cache->clear());
        $this->assertSame(null, $cache->get('foo'));
    }
    
    public function testGetMultipleMethod()
    {
        $cache = $this->createCache();
        $cache->set('foo', 'Foo');
        $cache->set('bar', 'Bar');
        
        $this->assertSame(
            ['foo' => 'Foo', 'bar' => 'Bar'],
            Iter::toArray(iterable: $cache->getMultiple(['foo', 'bar']))
        );
        
        $this->assertSame(
            ['foo' => 'Foo', 'bar' => 'Bar', 'another' => null],
            Iter::toArray(iterable: $cache->getMultiple(['foo', 'bar', 'another']))
        );
        
        $this->assertSame(
            ['foo' => 'Foo', 'bar' => 'Bar', 'another' => 'default'],
            Iter::toArray(iterable: $cache->getMultiple(['foo', 'bar', 'another'], 'default'))
        );
    }
    
    public function testSetMultipleMethodWithTtlNull()
    {
        $cache = $this->createCache();
        $this->assertTrue($cache->setMultiple([
            'foo' => 'Foo',
            'bar' => 'Bar',
        ], ttl: null));
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+100 years'),
        );
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
    }
    
    public function testSetMultipleMethodWithTtlSeconds()
    {
        $cache = $this->createCache();
        $this->assertTrue($cache->setMultiple([
            'foo' => 'Foo',
            'bar' => 'Bar',
        ], ttl: 30));
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+29 seconds'),
        );
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+31 seconds'),
        );
        
        $this->assertSame(null, $cache->get('foo'));
        $this->assertSame(null, $cache->get('bar'));
    }
    
    public function testSetMultipleMethodWithTtlDateInterval()
    {
        $cache = $this->createCache();
        $this->assertTrue($cache->setMultiple([
            'foo' => 'Foo',
            'bar' => 'Bar',
        ], ttl: new DateInterval('PT30S')));
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+29 seconds'),
        );
        
        $this->assertSame('Foo', $cache->get('foo'));
        $this->assertSame('Bar', $cache->get('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+31 seconds'),
        );
        
        $this->assertSame(null, $cache->get('foo'));
        $this->assertSame(null, $cache->get('bar'));
    }
    
    public function testDeleteMultipleMethod()
    {
        $cache = $this->createCache();
        $cache->set('foo', 'Foo');
        $cache->set('bar', 'Bar');
        $cache->set('another', 'Another');
        
        $this->assertTrue($cache->deleteMultiple(['foo', 'bar']));
        
        $this->assertSame(null, $cache->get('foo'));
        $this->assertSame(null, $cache->get('bar'));
        $this->assertSame('Another', $cache->get('another'));
    }
    
    public function testHasMethod()
    {
        $cache = $this->createCache();
        $cache->set('foo', 'Foo');
        
        $this->assertTrue($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
    
    public function testDeleteExpiredItems()
    {
        $tables = new Tables();
        $tables->add('cache_items', ['id', 'data', 'expiration', 'namespace'], 'id');

        $storage = new JsonFileStorage(
            dir: __DIR__.'/tmp/',
            tables: $tables
        );
        
        $cache = $this->createCache(storage: $storage);
        
        $cache->set(key: 'foo', value: 'value', ttl: 30);
        $cache->set(key: 'bar', value: 'value', ttl: 40);
        $this->assertTrue($cache->has('foo'));
        $this->assertTrue($cache->has('bar'));

        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+35 seconds')
        );
        
        $this->assertSame(2, $storage->get()->count());
        $this->assertTrue($cache->deleteExpiredItems());
        $this->assertSame(1, $storage->get()->count());
        $this->assertFalse($cache->has('foo'));
        $this->assertTrue($cache->has('bar'));
        
        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+41 seconds')
        );
        
        $this->assertTrue($cache->deleteExpiredItems());
        
        $this->assertSame(0, $storage->get()->count());
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }
    
    public function AtestDeleteExpiredItemsWithNeverExpiringItem()
    {
        $tables = new Tables();
        $tables->add('cache_items', ['id', 'data', 'expiration', 'namespace'], 'id');

        $storage = new JsonFileStorage(
            dir: __DIR__.'/tmp/',
            tables: $tables
        );
        
        $cache = $this->createCache(storage: $storage);
        
        $cache->set(key: 'foo', value: 'value', ttl: null);
        $this->assertTrue($cache->deleteExpiredItems());
        $this->assertTrue($cache->has('foo'));
        $this->assertSame(1, $storage->get()->count());

        $cache = $this->createCache(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($cache->deleteExpiredItems());
        $this->assertTrue($cache->has('foo'));
        $this->assertSame(1, $storage->get()->count());
    }
}