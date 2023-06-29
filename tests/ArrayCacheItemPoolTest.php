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

namespace Tobento\Service\Cache\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Cache\CacheException;
use Tobento\Service\Clock\FrozenClock;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * ArrayCacheItemPoolTest
 */
class ArrayCacheItemPoolTest extends TestCase
{
    protected function createPool(
        null|ClockInterface $clock = null,
        null|int $ttl = null,
    ): ArrayCacheItemPool {
        
        if (is_null($clock)) {
            $clock = new FrozenClock();
        }
        
        return new ArrayCacheItemPool(clock: $clock, ttl: $ttl);
    }
    
    public function testInterfaces()
    {
        $pool = $this->createPool();
        
        $this->assertInstanceof(CacheItemPoolInterface::class, $pool);
    }

    public function testGetItemMethod()
    {
        $pool = $this->createPool();
        
        $this->assertInstanceof(CacheItemInterface::class, $pool->getItem('foo'));
    }
    
    public function testGetItemsMethod()
    {
        $pool = $this->createPool();
        
        $items = $pool->getItems(['foo', 'bar']);
        
        $fooItem = $items['foo'] ?? null;
        $barItem = $items['bar'] ?? null;
        
        $this->assertInstanceof(CacheItemInterface::class, $fooItem);
        $this->assertSame('foo', $fooItem?->getKey());
        
        $this->assertInstanceof(CacheItemInterface::class, $barItem);
        $this->assertSame('bar', $barItem?->getKey());
    }
    
    public function testGetItemsMethodWithEmptyKeys()
    {
        $this->assertSame([], $this->createPool()->getItems([]));
    }
    
    public function testHasItemMethod()
    {
        $pool = $this->createPool();
        
        $pool->save($pool->getItem('foo'));
        
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertFalse($pool->hasItem('bar'));
    }
    
    public function testClearMethod()
    {
        $pool = $this->createPool();
        $pool->save($pool->getItem('foo'));
        
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->clear());
        $this->assertFalse($pool->hasItem('foo'));
    }
    
    public function testClearMethodWithNoItems()
    {
        $pool = $this->createPool();
        
        $this->assertTrue($pool->clear());
        $this->assertFalse($pool->hasItem('foo'));
    }
    
    public function testDeleteItemMethod()
    {
        $pool = $this->createPool();
        $pool->save($pool->getItem('foo'));
        
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->deleteItem('foo'));
        $this->assertFalse($pool->hasItem('foo'));
        $this->assertTrue($pool->deleteItem('bar'));
    }
    
    public function testDeleteItemsMethod()
    {
        $pool = $this->createPool();
        $pool->save($pool->getItem('foo'));
        
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->deleteItems(['foo']));
        $this->assertFalse($pool->hasItem('foo'));
    }

    public function testSaveMethod()
    {
        $pool = $this->createPool();
        $pool->save($pool->getItem('foo'));
        $this->assertTrue($pool->hasItem('foo'));
    }
    
    public function testSaveMethodWithHit()
    {
        $pool = $this->createPool();
        
        $item = $pool->getItem('foo');
        
        $this->assertFalse($item->isHit());
        
        $pool->save($item);
        
        $this->assertTrue($item->isHit());
    }

    public function testSaveDeferredMethod()
    {
        $pool = $this->createPool();
        $pool->saveDeferred($pool->getItem('foo'));
        
        $this->assertFalse($pool->hasItem('foo'));
        
        $pool->commit();
        
        $this->assertTrue($pool->hasItem('foo'));
    }
    
    public function testSaveDeferredMethodWithHit()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        
        $this->assertFalse($item->isHit());
        
        $pool->saveDeferred($item);
        
        $this->assertFalse($item->isHit());
        
        $pool->commit();
        
        $this->assertTrue($item->isHit());
    }
    
    public function testSaveDeferredMethodWithClear()
    {
        $pool = $this->createPool();
        $pool->saveDeferred($pool->getItem('foo'));
        $pool->commit();
        
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->clear());
        $this->assertFalse($pool->hasItem('foo'));
    }
    
    public function testGetItemAndSaveWorkflow()
    {
        $pool = $this->createPool();
        
        $item = $pool->getItem('foo');
        
        $this->assertFalse($item->isHit());

        $item->set('value');
        
        $pool->save($item);
        
        $fetchedItem = $pool->getItem('foo');
        
        $this->assertSame('value', $fetchedItem->get());
        
        $this->assertTrue($fetchedItem->isHit());
    }
    
    public function testItemWithExpiresAtNull()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAt(null);
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($item->isHit());
    }
    
    public function testItemWithExpiresAtDateTime()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAt((new DateTimeImmutable('now'))->modify('+30 seconds'));
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($item->isHit());
    }

    public function testItemWithExpiresAfterNull()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(null);
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($item->isHit());
    }
    
    public function testItemWithExpiresAfterSeconds()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(30);
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($item->isHit());
    }
    
    public function testItemWithExpiresAfterDateInterval()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(new DateInterval('PT30S'));
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($item->isHit());
    }
    
    public function testWithDefaultTtlNull()
    {
        $pool = $this->createPool(ttl: null);
        $item = $pool->getItem('foo');
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertTrue($item->isHit());
    }
    
    public function testWithDefaultTtlSeconds()
    {
        $pool = $this->createPool(ttl: 30);
        $item = $pool->getItem('foo');
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $item = $pool->getItem('foo')->setClock(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($item->isHit());
    }
}