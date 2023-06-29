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
use Psr\Cache\CacheException;

/**
 * CacheItemPoolsInterface
 */
interface CacheItemPoolsInterface
{
    /**
     * Add a pool.
     *
     * @param string $name The pool name.
     * @param CacheItemPoolInterface $pool
     * @return static $this
     */
    public function add(string $name, CacheItemPoolInterface $pool): static;
    
    /**
     * Register a pool.
     *
     * @param string $name The pool name.
     * @param callable $pool
     * @return static $this
     */
    public function register(string $name, callable $pool): static;
    
    /**
     * Returns the pool by name.
     *
     * @param string $name The pool name
     * @return CacheItemPoolInterface
     * @throws CacheException
     */
    public function get(string $name): CacheItemPoolInterface;
    
    /**
     * Returns true if the pool exists, otherwise false.
     *
     * @param string $name The pool name.
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Adds a default name for the specified pool.
     *
     * @param string $name The default name.
     * @param string $pool The pool name.
     * @return static $this
     */
    public function addDefault(string $name, string $pool): static;

    /**
     * Returns the default pools.
     *
     * @return array<string, string> ['general' => 'files']
     */
    public function getDefaults(): array;
    
    /**
     * Returns the pool for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return CacheItemPoolInterface
     * @throws CacheException
     */
    public function default(string $name): CacheItemPoolInterface;
    
    /**
     * Returns true if the default pool exists, otherwise false.
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