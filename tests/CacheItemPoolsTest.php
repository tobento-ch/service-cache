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
use Tobento\Service\Cache\CacheItemPools;
use Tobento\Service\Cache\CacheItemPoolsInterface;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Clock\FrozenClock;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;

/**
 * CacheItemPoolsTest
 */
class CacheItemPoolsTest extends TestCase
{
    protected function createPool(): CacheItemPoolInterface
    {
        return new ArrayCacheItemPool(
            clock: new FrozenClock(),
        );
    }
    
    public function testInterfaces()
    {
        $this->assertInstanceOf(CacheItemPoolsInterface::class, new CacheItemPools());
    }
    
    public function testAddMethod()
    {
        $pools = new CacheItemPools();
        
        $this->assertFalse($pools->has('arr'));
        
        $pool = $this->createPool();
        
        $pools->add(name: 'arr', pool: $pool);
        
        $this->assertTrue($pools->has('arr'));
        $this->assertSame($pool, $pools->get('arr'));
    }
    
    public function testRegisterMethod()
    {
        $pools = new CacheItemPools();
        
        $this->assertFalse($pools->has('arr'));
        
        $pools->register(
            name: 'arr',
            pool: function(string $name): CacheItemPoolInterface {        
                return $this->createPool();
            }
        );
            
        $this->assertTrue($pools->has('arr'));
        $this->assertInstanceof(CacheItemPoolInterface::class, $pools->get('arr'));
    }

    public function testGetMethodThrowsCacheExceptionIfNotExist()
    {
        $this->expectException(CacheException::class);
        
        $pools = new CacheItemPools();
        
        $pools->get('arr'); 
    }
    
    public function testHasMethod()
    {
        $pools = new CacheItemPools();
        
        $this->assertFalse($pools->has('arr'));
        
        $pools->add('arr', $this->createPool());
            
        $this->assertTrue($pools->has('arr'));
        
        $this->assertFalse($pools->has('foo')); 
    }
    
    public function testAddDefaultMethod()
    {
        $pools = new CacheItemPools();
        
        $pools->addDefault(name: 'primary', pool: 'arr');
        
        $this->assertTrue(true);
    }
    
    public function testDefaultMethod()
    {
        $pools = new CacheItemPools();
        
        $pool = $this->createPool();
        
        $pools->add('arr', $pool);
        
        $pools->addDefault(name: 'primary', pool: 'arr');
            
        $this->assertSame(
            $pool,
            $pools->default('primary')
        );
    }
    
    public function testDefaultMethodThrowsCacheExceptionIfNotExist()
    {
        $this->expectException(CacheException::class);
        
        $pools = new CacheItemPools();

        $pools->default('primary');
    }
    
    public function testHasDefaultMethod()
    {
        $pools = new CacheItemPools();
        
        $pool = $this->createPool('arr');
        
        $pools->add('arr', $pool);
        
        $pools->addDefault(name: 'primary', pool: 'arr');
        
        $this->assertTrue($pools->hasDefault('primary'));
        
        $this->assertFalse($pools->hasDefault('foo')); 
    }
    
    public function testGetDefaultsMethod()
    {
        $pools = new CacheItemPools();
        
        $pools->addDefault(name: 'primary', pool: 'arr');
        $pools->addDefault(name: 'secondary', pool: 'file');
        
        $this->assertSame(
            [
                'primary' => 'arr',
                'secondary' => 'file',
            ],
            $pools->getDefaults()
        );
    }
    
    public function testGetNamesMethod()
    {
        $pools = new CacheItemPools();
        
        $this->assertSame([], $pools->getNames());
        
        $pools->add(name: 'arr', pool: $this->createPool());
        
        $pools->register(
            'arr1',
            function(string $name): CacheItemPoolInterface {
                return $this->createPool();
            }
        );
        
        $this->assertSame(['arr', 'arr1'], $pools->getNames());
    }
}