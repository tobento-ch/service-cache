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

use Tobento\Service\Cache\CacheException as ServiceCacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;
use Throwable;

/**
 * CacheItemPools
 */
class CacheItemPools implements CacheItemPoolsInterface
{
    /**
     * @var array<string, callable|CacheItemPoolInterface>
     */
    protected array $pools = [];
    
    /**
     * @var array<string, string> The default pools. ['general' => 'files']
     */
    protected array $defaults = [];
    
    /**
     * Add a pool.
     *
     * @param string $name The pool name.
     * @param CacheItemPoolInterface $pool
     * @return static $this
     */
    public function add(string $name, CacheItemPoolInterface $pool): static
    {
        $this->pools[$name] = $pool;
        return $this;
    }
    
    /**
     * Register a pool.
     *
     * @param string $name The pool name.
     * @param callable $pool
     * @return static $this
     */
    public function register(string $name, callable $pool): static
    {
        $this->pools[$name] = $pool;
        return $this;
    }
    
    /**
     * Returns the pool by name.
     *
     * @param string $name The pool name
     * @return CacheItemPoolInterface
     * @throws CacheException
     */
    public function get(string $name): CacheItemPoolInterface
    {
        if (!$this->has($name)) {
            throw new ServiceCacheException(sprintf('Pool %s not found!', $name));
        }
        
        if (! $this->pools[$name] instanceof CacheItemPoolInterface) {
            try {
                $this->pools[$name] = $this->createPool($name, $this->pools[$name]);
            } catch(Throwable $e) {
                throw new ServiceCacheException($e->getMessage(), 0, $e);
            }
        }
        
        return $this->pools[$name];
    }
    
    /**
     * Returns true if the pool exists, otherwise false.
     *
     * @param string $name The pool name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->pools);
    }

    /**
     * Adds a default name for the specified pool.
     *
     * @param string $name The default name.
     * @param string $pool The pool name.
     * @return static $this
     */
    public function addDefault(string $name, string $pool): static
    {
        $this->defaults[$name] = $pool;
        return $this;
    }

    /**
     * Returns the default pools.
     *
     * @return array<string, string> ['general' => 'files']
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }
    
    /**
     * Returns the pool for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return CacheItemPoolInterface
     * @throws CacheException
     */
    public function default(string $name): CacheItemPoolInterface
    {
        if (!$this->hasDefault($name)) {
            throw new ServiceCacheException(sprintf('Default pool %s not found!', $name));
        }
        
        return $this->get($this->defaults[$name]);
    }
    
    /**
     * Returns true if the default pool exists, otherwise false.
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
        return array_keys($this->pools);
    }
    
    /**
     * Create a new Storage.
     *
     * @param string $name
     * @param callable $factory
     * @return CacheItemPoolInterface
     */
    protected function createPool(string $name, callable $factory): CacheItemPoolInterface
    {
        return call_user_func_array($factory, [$name]);
    }
}