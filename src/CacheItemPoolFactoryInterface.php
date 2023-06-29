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
 * CacheItemPoolFactoryInterface
 */
interface CacheItemPoolFactoryInterface
{
    /**
     * Create a new CacheItemPool based on the configuration.
     *
     * @param string $name
     * @param array $config
     * @return CacheItemPoolInterface
     * @throws CacheException
     */
    public function createCacheItemPool(string $name, array $config = []): CacheItemPoolInterface;
}