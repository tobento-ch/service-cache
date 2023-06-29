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

namespace Tobento\Service\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\FolderException;

/**
 * ArrayCacheItemPool
 */
final class ArrayCacheItemPool implements CacheItemPoolInterface
{
    /**
     * The array<string, CacheItem>
     */
    private array $items = [];
    
    /**
     * @var array<string, CacheItemInterface>
     */
    private array $deferredItems = [];
    
    /**
     * Create a new ArrayCacheItemPool.
     *
     * @param ClockInterface $clock
     * @param null|int $ttl The default Time To Live in seconds. Null forever.
     */
    public function __construct(
        private ClockInterface $clock,
        private null|int $ttl = null,
    ) {}
    
    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem(string $key): CacheItemInterface
    {
        if (isset($this->deferredItems[$key])) {
            return $this->deferredItems[$key];
        }

        if (!isset($this->items[$key])) {
            return $this->freshCacheItem(key: $key);
        }
        
        $item = $this->items[$key];
        
        if ($item->isHit()) {
            return $item;
        }
        
        $this->deleteItem($item->getKey());
        
        return $this->freshCacheItem(key: $key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return iterable
     *   An iterable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        
        foreach($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $this->items = [];
        $this->deferredItems = [];
        return true;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);
        unset($this->deferredItems[$key]);
        return true;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys): bool
    {
        foreach($keys as $key) {
            $this->deleteItem($key);
        }
        
        return true;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem) {
            return false;
        }
        
        $item->setHit(true);
        $this->items[$item->getKey()] = $item;
        return true;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferredItems[$item->getKey()] = $item;
        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit(): bool
    {
        foreach($this->deferredItems as $key => $item) {
            if ($this->save($item)) {
                unset($this->deferredItems[$key]);
            }
        }

        return empty($this->deferredItems);
    }
    
    /**
     * Returns the storage path for the specified cache key.
     *
     * @param string $key
     * @return CacheItem
     */
    private function freshCacheItem(string $key): CacheItem
    {
        return (new CacheItem(
            key: $key,
            value: null,
            clock: $this->clock,
            expiration: null,
            hit: false,
        ))->expiresAfter($this->ttl);
    }
    
    /**
     * Commit the deferred items ~ acts as the last resort garbage collector.
     */
    public function __destruct()
    {
        $this->commit();
    }
}