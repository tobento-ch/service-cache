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

namespace Tobento\Service\Cache\Test\Simple;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Cache\Simple\Caches;
use Tobento\Service\Cache\Simple\CachesInterface;
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Clock\FrozenClock;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException;

/**
 * CachesTest
 */
class CachesTest extends TestCase
{
    protected function createCache(): CacheInterface
    {
        $pool = new ArrayCacheItemPool(
            clock: new FrozenClock(),
        );
        
        return new Psr6Cache(
            pool: $pool,
            namespace: 'ns',
            ttl: null,
        );
    }
    
    public function testInterfaces()
    {
        $this->assertInstanceOf(CachesInterface::class, new Caches());
    }
    
    public function testAddMethod()
    {
        $caches = new Caches();
        
        $this->assertFalse($caches->has('arr'));
        
        $cache = $this->createCache();
        
        $caches->add(name: 'arr', cache: $cache);
        
        $this->assertTrue($caches->has('arr'));
        $this->assertSame($cache, $caches->get('arr'));
    }
    
    public function testRegisterMethod()
    {
        $caches = new Caches();
        
        $this->assertFalse($caches->has('arr'));
        
        $caches->register(
            name: 'arr',
            cache: function(string $name): CacheInterface {        
                return $this->createCache();
            }
        );
            
        $this->assertTrue($caches->has('arr'));
        $this->assertInstanceof(CacheInterface::class, $caches->get('arr'));
    }

    public function testGetMethodThrowsCacheExceptionIfNotExist()
    {
        $this->expectException(CacheException::class);
        
        $caches = new Caches();
        
        $caches->get('arr'); 
    }
    
    public function testHasMethod()
    {
        $caches = new Caches();
        
        $this->assertFalse($caches->has('arr'));
        
        $caches->add('arr', $this->createCache());
            
        $this->assertTrue($caches->has('arr'));
        
        $this->assertFalse($caches->has('foo')); 
    }
    
    public function testAddDefaultMethod()
    {
        $caches = new Caches();
        
        $caches->addDefault(name: 'primary', cache: 'arr');
        
        $this->assertTrue(true);
    }
    
    public function testDefaultMethod()
    {
        $caches = new Caches();
        
        $cache = $this->createCache();
        
        $caches->add('arr', $cache);
        
        $caches->addDefault(name: 'primary', cache: 'arr');
            
        $this->assertSame(
            $cache,
            $caches->default('primary')
        );
    }
    
    public function testDefaultMethodThrowsCacheExceptionIfNotExist()
    {
        $this->expectException(CacheException::class);
        
        $caches = new Caches();

        $caches->default('primary');
    }
    
    public function testHasDefaultMethod()
    {
        $caches = new Caches();
        
        $cache = $this->createCache('arr');
        
        $caches->add('arr', $cache);
        
        $caches->addDefault(name: 'primary', cache: 'arr');
        
        $this->assertTrue($caches->hasDefault('primary'));
        
        $this->assertFalse($caches->hasDefault('foo')); 
    }
    
    public function testGetDefaultsMethod()
    {
        $caches = new Caches();
        
        $caches->addDefault(name: 'primary', cache: 'arr');
        $caches->addDefault(name: 'secondary', cache: 'file');
        
        $this->assertSame(
            [
                'primary' => 'arr',
                'secondary' => 'file',
            ],
            $caches->getDefaults()
        );
    }
    
    public function testGetNamesMethod()
    {
        $caches = new Caches();
        
        $this->assertSame([], $caches->getNames());
        
        $caches->add(name: 'arr', cache: $this->createCache());
        
        $caches->register(
            'arr1',
            function(string $name): CacheInterface {
                return $this->createCache();
            }
        );
        
        $this->assertSame(['arr', 'arr1'], $caches->getNames());
    }
}