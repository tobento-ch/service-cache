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

namespace Tobento\Service\Cache\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Cache\InvalidArgumentException;
use Psr\Cache\CacheException as Psr6CacheInterface;
use Psr\SimpleCache\CacheException as Psr16CacheInterface;
use Exception;

/**
 * InvalidArgumentExceptionTest
 */
class InvalidArgumentExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new InvalidArgumentException('message');
        
        $this->assertInstanceof(Psr6CacheInterface::class, $e);
        $this->assertInstanceof(Psr16CacheInterface::class, $e);
        $this->assertInstanceof(Exception::class, $e);
    }
}