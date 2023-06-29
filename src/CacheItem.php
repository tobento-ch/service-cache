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
use DateTimeInterface;
use DateInterval;

/**
 * CacheItem
 */
class CacheItem implements CacheItemInterface
{
    /**
     * Create a new CacheItemPool.
     *
     * @param string $name
     * @param mixed $value The item value (unserialized)
     * @param ClockInterface $clock
     * @param null|DateTimeInterface $expiration
     * @param bool $hit
     */
    public function __construct(
        protected string $key,
        protected mixed $value,
        protected ClockInterface $clock,
        protected null|DateTimeInterface $expiration = null,
        protected bool $hit = false,
    ) {
        $this->expiresAt($expiration);
    }
    
    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get(): mixed
    {
        return $this->hit ? $this->value : null;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit(): bool
    {
        if (! $this->hit) {
            return false;
        }
        
        if (
            !is_null($this->expiration)
            && $this->expiration < $this->clock->now()
        ) {
            return false;
        }

        return true;
        
        //return $this->hit && $this->getTtlInSecond() > 0;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set(mixed $value): static
    {
        $this->value = $value;
        $this->hit = false;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param ?\DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if (is_int($time)) {
            $modified = $this->clock->now()->modify('+'.$time.' seconds');
            $this->expiration = $modified ?: null;
        } elseif ($time instanceof DateInterval) {
            $this->expiration = $this->clock->now()->add($time);
        } else {
            $this->expiration = null;
        }

        return $this;
    }

    /**
     * Returns the expiration.
     *
     * @param bool $hit
     * @return static $this
     */
    public function setHit(bool $hit): static
    {
        $this->hit = $hit;
        return $this;
    }
    
    /**
     * Sets the clock.
     *
     * @param ClockInterface $clock
     * @return static $this
     */
    public function setClock(ClockInterface $clock): static
    {
        $this->clock = $clock;
        return $this;
    }
    
    /**
     * Returns the expiration.
     *
     * @return null|DateTimeInterface
     */
    public function getExpiration(): null|DateTimeInterface
    {
        return $this->expiration;
    }
}