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

namespace Tobento\Service\Cache\Simple;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as Psr6InvalidArgumentException;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Cache\InvalidArgumentException;
use Tobento\Service\Iterable\Iter;
use DateInterval;

/**
 * Psr6Cache
 */
final class Psr6Cache implements CacheInterface, CanDeleteExpiredItems
{
    /**
     * Create a new Psr6Cache.
     *
     * @param CacheItemPoolInterface $pool
     * @param string $namespace
     * @param null|int $ttl The default Time To Live in seconds.
     */
    public function __construct(
        private CacheItemPoolInterface $pool,
        private string $namespace,
        private null|int $ttl = null,
    ) {}
    
    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $item = $this->pool->getItem($key);
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        try {
            $item = $this->pool->getItem($key);
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        
        $item->expiresAfter($ttl ?: $this->ttl);
        $item->set($value);

        return $this->pool->save($item);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool
    {
        try {
            return $this->pool->deleteItem($key);
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys    A list of keys that can be obtained in a single operation.
     * @param mixed            $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        try {
            $items = $this->pool->getItems(Iter::toArray(iterable: $keys));
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        foreach($items as $key => $item) {
            if (!$item->isHit()) {
                yield $key => $default;
            } else {
                yield $key => $item->get();
            }
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $values = Iter::toArray(iterable: $values);

        try {
            $items = $this->pool->getItems(array_keys($values));
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        $success = true;

        foreach($items as $key => $item) {
            
            $item->set($values[$key] ?? null);

            $item->expiresAfter($ttl ?: $this->ttl);

            $success = $success && $this->pool->saveDeferred($item);
        }

        return $success && $this->pool->commit();
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        try {
            return $this->pool->deleteItems(Iter::toArray(iterable: $keys));
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        try {
            return $this->pool->hasItem($key);
        } catch (Psr6InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * Removes all expired items from the pool or cache.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteExpiredItems(): bool
    {
        if ($this->pool instanceof CanDeleteExpiredItems) {
            return $this->pool->deleteExpiredItems();
        }
        
        return false;
    }
    
    /**
     * Returns the cache key for the specified cache item key.
     *
     * @param string $key
     * @return string
     */
    private function toCacheKey(string $key): string
    {
        return $this->namespace.'.'.sha1($key);
    }
}