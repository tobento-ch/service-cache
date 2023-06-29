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
use Tobento\Service\Cache\FileStorageCacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Cache\CacheException;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\Flysystem;
use Tobento\Service\Filesystem\Dir;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * FileStorageCacheItemPoolTest
 */
class FileStorageCacheItemPoolTest extends TestCase
{
    public function setUp(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }

    public function tearDown(): void
    {
        (new Dir())->delete(__DIR__.'/tmp/');
    }
    
    protected function createPool(
        null|StorageInterface $storage = null,
        string $path = 'cache',
        null|ClockInterface $clock = null,
        null|int $ttl = null,
    ): FileStorageCacheItemPool {
        
        if (is_null($storage)) {
            $filesystem = new \League\Flysystem\Filesystem(
                adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                    location: __DIR__.'/tmp',
                )
            );

            $storage = new Flysystem\Storage(
                name: 'local',
                flysystem: $filesystem,
                fileFactory: new Flysystem\FileFactory(
                    flysystem: $filesystem,
                    streamFactory: new Psr17Factory()
                ),
            );
        }
        
        if (is_null($clock)) {
            $clock = new FrozenClock();
        }
        
        return new FileStorageCacheItemPool(
            storage: $storage,
            path: $path,
            clock: $clock,
            ttl: $ttl,
        );
    }

    public function testThrowsCacheExceptionIfEmptyPath()
    {
        $this->expectException(CacheException::class);
        
        $pool = $this->createPool(path: '');
    }
    
    public function testInterfaces()
    {
        $pool = $this->createPool();
        
        $this->assertInstanceof(CacheItemPoolInterface::class, $pool);
        $this->assertInstanceof(CanDeleteExpiredItems::class, $pool);
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
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
    }
    
    public function testItemWithExpiresAtDateTime()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAt((new DateTimeImmutable('now'))->modify('+30 seconds'));
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($pool->getItem('foo')->isHit());
    }

    public function testItemWithExpiresAfterNull()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(null);
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
    }
    
    public function testItemWithExpiresAfterSeconds()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(30);
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($pool->getItem('foo')->isHit());
    }
    
    public function testItemWithExpiresAfterDateInterval()
    {
        $pool = $this->createPool();
        $item = $pool->getItem('foo');
        $item->expiresAfter(new DateInterval('PT30S'));
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($pool->getItem('foo')->isHit());
    }

    public function testWithDefaultTtlNull()
    {
        $pool = $this->createPool(ttl: null);
        $item = $pool->getItem('foo');
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
    }
    
    public function testWithDefaultTtlSeconds()
    {
        $pool = $this->createPool(ttl: 30);
        $item = $pool->getItem('foo');
        $this->assertFalse($item->isHit());
        $pool->save($item);
        $this->assertTrue($pool->getItem('foo')->isHit());

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+29 seconds')
        );
        
        $this->assertTrue($pool->getItem('foo')->isHit());
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+31 seconds')
        );
        
        $this->assertFalse($pool->getItem('foo')->isHit());
    }
    
    public function testDeleteExpiredItems()
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/tmp',
            )
        );

        $storage = new Flysystem\Storage(
            name: 'local',
            flysystem: $filesystem,
            fileFactory: new Flysystem\FileFactory(
                flysystem: $filesystem,
                streamFactory: new Psr17Factory()
            ),
        );
        
        $pool = $this->createPool($storage);
        
        $item = $pool->getItem('foo');
        $item->expiresAfter(30);
        $pool->save($item);
        $this->assertTrue($pool->hasItem('foo'));
        
        $item = $pool->getItem('bar');
        $item->expiresAfter(40);
        $pool->save($item);
        $this->assertTrue($pool->hasItem('bar'));

        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+35 seconds')
        );
        
        $this->assertSame(2, count($storage->files(path: 'cache')->all()));
        $this->assertTrue($pool->deleteExpiredItems());
        $this->assertSame(1, count($storage->files(path: 'cache')->all()));
        $this->assertFalse($pool->hasItem('foo'));
        $this->assertTrue($pool->hasItem('bar'));
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+41 seconds')
        );
        
        $this->assertTrue($pool->deleteExpiredItems());
        $this->assertSame(0, count($storage->files(path: 'cache')->all()));
        $this->assertFalse($pool->hasItem('foo'));
        $this->assertFalse($pool->hasItem('bar'));
    }
    
    public function testDeleteExpiredItemsWithNeverExpiringItem()
    {
        $filesystem = new \League\Flysystem\Filesystem(
            adapter: new \League\Flysystem\Local\LocalFilesystemAdapter(
                location: __DIR__.'/tmp',
            )
        );

        $storage = new Flysystem\Storage(
            name: 'local',
            flysystem: $filesystem,
            fileFactory: new Flysystem\FileFactory(
                flysystem: $filesystem,
                streamFactory: new Psr17Factory()
            ),
        );
        
        $pool = $this->createPool();
        
        $item = $pool->getItem('foo');
        $item->expiresAfter(null);
        $pool->save($item);
        $this->assertSame(1, count($storage->files(path: 'cache')->all()));
        $this->assertTrue($pool->hasItem('foo'));
        $this->assertTrue($pool->deleteExpiredItems());
        $this->assertSame(1, count($storage->files(path: 'cache')->all()));
        $this->assertTrue($pool->hasItem('foo'));
        
        $pool = $this->createPool(
            clock: (new FrozenClock())->modify('+100 years')
        );
        
        $this->assertTrue($pool->deleteExpiredItems());
        $this->assertSame(1, count($storage->files(path: 'cache')->all()));
        $this->assertTrue($pool->hasItem('foo'));
    }
}