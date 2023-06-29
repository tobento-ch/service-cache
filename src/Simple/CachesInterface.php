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
use Psr\SimpleCache\CacheException;

/**
 * CachesInterface
 */
interface CachesInterface
{
    /**
     * Add a cache.
     *
     * @param string $name The cache name.
     * @param CacheInterface $cache
     * @return static $this
     */
    public function add(string $name, CacheInterface $cache): static;
    
    /**
     * Register a cache.
     *
     * @param string $name The cache name.
     * @param callable $cache
     * @return static $this
     */
    public function register(string $name, callable $cache): static;
    
    /**
     * Returns the cache by name.
     *
     * @param string $name The cache name
     * @return CacheInterface
     * @throws CacheException
     */
    public function get(string $name): CacheInterface;
    
    /**
     * Returns true if the cache exists, otherwise false.
     *
     * @param string $name The cache name.
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Adds a default name for the specified cache.
     *
     * @param string $name The default name.
     * @param string $cache The cache name.
     * @return static $this
     */
    public function addDefault(string $name, string $cache): static;

    /**
     * Returns the default caches.
     *
     * @return array<string, string> ['general' => 'files']
     */
    public function getDefaults(): array;
    
    /**
     * Returns the cache for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return CacheInterface
     * @throws CacheException
     */
    public function default(string $name): CacheInterface;
    
    /**
     * Returns true if the default cache exists, otherwise false.
     *
     * @param string $name The default name.
     * @return bool
     */
    public function hasDefault(string $name): bool;
    
    /**
     * Returns the names.
     *
     * @return array<int, string>
     */
    public function getNames(): array;
}