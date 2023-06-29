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
use Tobento\Service\Cache\CacheItem;
use Tobento\Service\Cache\CacheException;
use Tobento\Service\Clock\FrozenClock;
use Psr\Cache\CacheItemInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;
use DateInterval;
use DateTimeInterface;

/**
 * CacheItemTest
 */
class CacheItemTest extends TestCase
{
    protected function clock(): ClockInterface
    {
        return new FrozenClock();
    }
    
    public function testInterfaces()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
        );
        
        $this->assertInstanceof(CacheItemInterface::class, $item);
    }
    
    public function testGetKeyMethod()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
        );
        
        $this->assertSame('key', $item->getKey());
    }
    
    public function testGetMethod()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $this->assertSame('value', $item->get());
    }
    
    public function testGetMethodReturnsNullIfNotHit()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: false,
        );
        
        $this->assertSame(null, $item->get());
    }
    
    public function testGetMethodIsSameAsSet()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
            hit: true,
        );
        
        $item->set('value');
        $item->setHit(true);
        
        $this->assertSame('value', $item->get());
    }

    public function testIsHitWithExpirationNull()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
            expiration: null,
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+100 years'));
        
        $this->assertTrue($item->isHit());
    }
    
    public function testIsHitWithExpirationDateTime()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
            expiration: (new DateTimeImmutable('now'))->modify('+30 seconds'),
        );
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+29 seconds'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+31 seconds'));
        
        $this->assertFalse($item->isHit());
    }
    
    public function testIsHitMethodWithExpiresAtNull()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $item->expiresAt(null);
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+100 years'));
        
        $this->assertTrue($item->isHit());
    }
    
    public function testIsHitMethodWithExpiresAtDateTime()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $item->expiresAt((new DateTimeImmutable('now'))->modify('+30 seconds'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+29 seconds'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+31 seconds'));
        
        $this->assertFalse($item->isHit());
    }
    
    public function testIsHitMethodWithExpiresAfterNull()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $item->expiresAfter(null);
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+100 years'));
        
        $this->assertTrue($item->isHit());
    }
    
    public function testIsHitMethodWithExpiresAfterSeconds()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $item->expiresAfter(30);
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+29 seconds'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+31 seconds'));
        
        $this->assertFalse($item->isHit());
    }
    
    public function testIsHitMethodWithExpiresAfterDateInterval()
    {
        $item = new CacheItem(
            key: 'key',
            value: 'value',
            clock: $this->clock(),
            hit: true,
        );
        
        $item->expiresAfter(new DateInterval('PT30S'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+29 seconds'));
        
        $this->assertTrue($item->isHit());
        
        $item->setClock((new FrozenClock())->modify('+31 seconds'));
        
        $this->assertFalse($item->isHit());
    }
    
    public function testGetExpirationMethodWithNull()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
            expiration: null,
        );
        
        $this->assertSame(null, $item->getExpiration());
    }
    
    public function testGetExpirationMethodWithDateTime()
    {
        $expiration = new DateTimeImmutable('now');
        
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
            expiration: $expiration,
        );
        
        $this->assertSame($expiration, $item->getExpiration());
    }

    public function testGetExpirationMethodWithExpiresAt()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
            expiration: null,
        );
        
        $this->assertSame(null, $item->getExpiration());
        
        $expiration = new DateTimeImmutable('now');
        
        $this->assertSame($expiration, $item->expiresAt($expiration)->getExpiration());
    }
    
    public function testGetExpirationMethodWithExpiresAfter()
    {
        $item = new CacheItem(
            key: 'key',
            value: null,
            clock: $this->clock(),
            expiration: null,
        );
        
        $this->assertSame(null, $item->getExpiration());
        
        $this->assertInstanceof(DateTimeInterface::class, $item->expiresAfter(30)->getExpiration());
    }
}