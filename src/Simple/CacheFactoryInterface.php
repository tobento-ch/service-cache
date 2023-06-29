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
 * CacheFactoryInterface
 */
interface CacheFactoryInterface
{
    /**
     * Create a new Cache based on the configuration.
     *
     * @param string $name
     * @param array $config
     * @return CacheInterface
     * @throws CacheException
     */
    public function createCache(string $name, array $config = []): CacheInterface;
}