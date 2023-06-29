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

use Psr\Cache\CacheException as Psr6CacheInterface;
use Psr\SimpleCache\CacheException as Psr16CacheInterface;
use Exception;

/**
 * CacheException
 */
class CacheException extends Exception implements Psr6CacheInterface, Psr16CacheInterface
{
    //
}