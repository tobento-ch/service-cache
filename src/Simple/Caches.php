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

use Tobento\Service\Cache\CacheException as ServiceCacheException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException;
use Throwable;

/**
 * Caches
 */
class Caches implements CachesInterface
{
    /**
     * @var array<string, callable|CacheInterface>
     */
    protected array $caches = [];
    
    /**
     * @var array<string, string> The default caches. ['general' => 'files']
     */
    protected array $defaults = [];
    
    /**
     * Add a cache.
     *
     * @param string $name The cache name.
     * @param CacheInterface $cache
     * @return static $this
     */
    public function add(string $name, CacheInterface $cache): static
    {
        $this->caches[$name] = $cache;
        return $this;
    }
    
    /**
     * Register a cache.
     *
     * @param string $name The cache name.
     * @param callable $cache
     * @return static $this
     */
    public function register(string $name, callable $cache): static
    {
        $this->caches[$name] = $cache;
        return $this;
    }
    
    /**
     * Returns the cache by name.
     *
     * @param string $name The cache name
     * @return CacheInterface
     * @throws CacheException
     */
    public function get(string $name): CacheInterface
    {
        if (!$this->has($name)) {
            throw new ServiceCacheException(sprintf('Cache %s not found!', $name));
        }
        
        if (! $this->caches[$name] instanceof CacheInterface) {
            try {
                $this->caches[$name] = $this->createCache($name, $this->caches[$name]);
            } catch(Throwable $e) {
                throw new ServiceCacheException($e->getMessage(), 0, $e);
            }
        }
        
        return $this->caches[$name];
    }
    
    /**
     * Returns true if the cache exists, otherwise false.
     *
     * @param string $name The cache name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->caches);
    }

    /**
     * Adds a default name for the specified cache.
     *
     * @param string $name The default name.
     * @param string $cache The cache name.
     * @return static $this
     */
    public function addDefault(string $name, string $cache): static
    {
        $this->defaults[$name] = $cache;
        return $this;
    }

    /**
     * Returns the default caches.
     *
     * @return array<string, string> ['general' => 'files']
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
    
    /**
     * Returns the cache for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return CacheInterface
     * @throws CacheException
     */
    public function default(string $name): CacheInterface
    {
        if (!$this->hasDefault($name)) {
            throw new ServiceCacheException(sprintf('Default cache %s not found!', $name));
        }
        
        return $this->get($this->defaults[$name]);
    }
    
    /**
     * Returns true if the default cache exists, otherwise false.
     *
     * @param string $name The default name.
     * @return bool
     */
    public function hasDefault(string $name): bool
    {
        return array_key_exists($name, $this->defaults);
    }
    
    /**
     * Returns the names.
     *
     * @return array<int, string>
     */
    public function getNames(): array
    {
        return array_keys($this->caches);
    }
    
    /**
     * Create a new Storage.
     *
     * @param string $name
     * @param callable $factory
     * @return CacheInterface
     */
    protected function createCache(string $name, callable $factory): CacheInterface
    {
        return call_user_func_array($factory, [$name]);
    }
}