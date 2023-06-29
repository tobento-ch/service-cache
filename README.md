# Cache Service

Providing [PSR-6](https://www.php-fig.org/psr/psr-6/) and [PSR-16](https://www.php-fig.org/psr/psr-16/) caches for PHP applications.

## Table of Contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Highlights](#highlights)
- [Documentation](#documentation)
    - [PSR 6 Cache](#psr-6-cache)
        - [Available Cache Item Pools](#available-cache-item-pools)
            - [File Storage Cache Item Pool](#file-storage-cache-item-pool)
            - [Storage Cache Item Pool](#storage-cache-item-pool)
            - [Array Cache Item Pool](#array-cache-item-pool)
            - [Psr16 Cache Item Pool](#psr16-cache-item-pool)
        - [Cache Item Pools](#cache-item-pools)
        - [Interfaces](#interfaces)
            - [Cache Item Pool Factory Interface](#cache-item-pool-factory-interface)
            - [Cache Item Pools Interface](#cache-item-pools-interface)
    - [PSR 16 Simple Cache](#psr-16-simple-cache)
        - [Available Caches](#available-caches)
            - [Psr6 Cache](#psr6-cache)
        - [Caches](#caches)
        - [Interfaces Simple](#interfaces-simple)
            - [Cache Factory Interface](#cache-factory-interface)
            - [Caches Interface](#caches-interface)
    - [Shared Interfaces](#shared-interfaces)
        - [Can Delete Expired Items](#can-delete-expired-items)
- [Credits](#credits)
___

# Getting started

Add the latest version of the cache service project running this command.

```
composer require tobento/service-cache
```

## Requirements

- PHP 8.0 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design

# Documentation

## PSR 6 Cache

### Available Cache Item Pools

#### File Storage Cache Item Pool

The file storage cache item pool using the [File Storage Service](https://github.com/tobento-ch/service-file-storage) to store the items.

First, you will need to install ```service-file-storage```:

```
composer require tobento/service-file-storage
```

Then, create the [File Storage](https://github.com/tobento-ch/service-file-storage#flysystem-storage) you wish to use for pool:

```php
use Tobento\Service\Cache\FileStorageCacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Clock\SystemClock;
use Tobento\Service\FileStorage\StorageInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

$pool = new FileStorageCacheItemPool(
    
    // Any storage where to store cache items:
    storage: $storage, // StorageInterface
    
    // A path used as the path prefix for the storage:
    path: 'cache', // string
    
    // The clock used for calculating expiration:
    clock: new SystemClock(), // ClockInterface
    
    // The default Time To Live in seconds, null forever:
    ttl: null, // null|int
);

var_dump($pool instanceof CacheItemPoolInterface);
// bool(true)

var_dump($pool instanceof CanDeleteExpiredItems);
// bool(true)
```

Check out the [Can Delete Expired Items](#can-delete-expired-items) interface to learn more about it.

#### Storage Cache Item Pool

The storage cache item pool using the [Storage Service](https://github.com/tobento-ch/service-storage) to store the items.

First, you will need to install ```service-storage```:

```
composer require tobento/service-storage
```

Then, create the [Storage](https://github.com/tobento-ch/service-storage#storages) you wish to use for pool:

```php
use Tobento\Service\Cache\StorageCacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Clock\SystemClock;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\Tables\Tables;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

// Create the storage:
$tables = new Tables();
$tables->add('cache_items', ['id', 'data', 'expiration', 'namespace'], 'id');

$storage = new JsonFileStorage(
    dir: __DIR__.'/tmp/',
    tables: $tables
);

$pool = new StorageCacheItemPool(
    
    // Any storage where to store cache items:
    storage: $storage, // StorageInterface
    
    // A namespace used as prefix for cache item keys:
    namespace: 'default', // string
    
    // The clock used for calculating expiration:
    clock: new SystemClock(), // ClockInterface
    
    // The default Time To Live in seconds, null forever:
    ttl: null, // null|int
    
    // Specify the table name:
    table: 'cache_items', // string
);

var_dump($pool instanceof CacheItemPoolInterface);
// bool(true)

var_dump($pool instanceof CanDeleteExpiredItems);
// bool(true)
```

Check out the [Can Delete Expired Items](#can-delete-expired-items) interface to learn more about it.

**Recommended table column types for (database) storage**

| Column | Type |
| --- | --- |
| id | VARCHAR(255) NOT NULL PRIMARY KEY |
| data | MEDIUMBLOB NOT NULL |
| expiration | TIMESTAMP |
| namespace | VARCHAR(100) NOT NULL |

#### Array Cache Item Pool

The cache items will be stored in memory and not persisted outside the running PHP process in any way. Might be useful for testing purposes.

```php
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Clock\SystemClock;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

$pool = new ArrayCacheItemPool(
    
    // The clock used for calculating expiration:
    clock: new SystemClock(), // ClockInterface
    
    // The default Time To Live in seconds, null forever:
    ttl: null, // null|int
);

var_dump($pool instanceof CacheItemPoolInterface);
// bool(true)
```

#### Psr16 Cache Item Pool

The Psr16 cache item pool using the defined Psr16 cache to store items.

```php
use Tobento\Service\Cache\Psr16CacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Clock\SystemClock;
use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

$pool = new Psr16CacheItemPool(
    
    cache: $cache, // CacheInterface
    
    // A namespace used as prefix for cache item keys:
    namespace: 'default', // string
    
    // The clock used for calculating expiration:
    clock: new SystemClock(), // ClockInterface
    
    // The default Time To Live in seconds, null forever:
    ttl: null, // null|int
);

var_dump($pool instanceof CacheItemPoolInterface);
// bool(true)

var_dump($pool instanceof CanDeleteExpiredItems);
// bool(true)
```

### Cache Item Pools

#### Create Pools

```php
use Tobento\Service\Cache\CacheItemPools;
use Tobento\Service\Cache\CacheItemPoolsInterface;

$pools = new CacheItemPools();

var_dump($pools instanceof CacheItemPoolsInterface);
// bool(true)
```

#### Add Pools

**add**

```php
use Psr\Cache\CacheItemPoolInterface;

$pools->add(
    name: 'primary',
    pool: $pool, // CacheItemPoolInterface
);
```

**register**

You may use the register method to only create the pool if requested.

```php
use Psr\Cache\CacheItemPoolInterface;

$pools->register(
    name: 'name',
    pool: function(string $name): CacheItemPoolInterface {
        // create the pool:
        return $pool;
    },
);
```

#### Get Pool

If the pool does not exist or could not get created it throws a ```CacheException::class```.

```php
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;

$pool = $pools->get(name: 'file');

var_dump($pool instanceof CacheItemPoolInterface);
// bool(true)

$pools->get(name: 'unknown');
// throws CacheException
```

You may use the ```has``` method to check if a pool exists.

```php
var_dump($pools->has('name'));
// bool(false)
```

You may use the ```getNames``` method to get all pool names.

```php
var_dump($pools->getNames());
// array(1) { [0]=> string(4) "file" }
```

#### Default Pools

You may add default pools for your application design.

```php
use Tobento\Service\Cache\CacheItemPools;
use Tobento\Service\Cache\CacheItemPoolsInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;

$pools = new CacheItemPools();

// add "file" pool:
$pools->add(name: 'file', pool: $pool);

// add default:
$pools->addDefault(name: 'primary', pool: 'file');

// get default pool for the specified name.
$primaryPool = $pools->default('primary');

var_dump($primaryPool instanceof CacheItemPoolInterface);
// bool(true)

var_dump($pools->hasDefault('primary'));
// bool(true)

var_dump($pools->getDefaults());
// { ["primary"]=> string(4) "file" }

$pools->default('unknown');
// throws CacheException
```

### Interfaces

#### Cache Item Pool Factory Interface

You may use this interface for creating pools.

```php
namespace Tobento\Service\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;

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
```

#### Cache Item Pools Interface

```php
namespace Tobento\Service\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheException;

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
```

## PSR 16 Simple Cache

### Available Caches

#### Psr6 Cache

The Psr6 cache using the defined Psr6 pool to store items.

```php
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Cache\CanDeleteExpiredItems;
use Tobento\Service\Clock\SystemClock;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Clock\ClockInterface;

$pool = new ArrayCacheItemPool(
    clock: new SystemClock(),
);

$cache = new Psr6Cache(
    
    pool: $pool, // CacheItemPoolInterface
    
    // A namespace used as prefix for cache item keys:
    namespace: 'default', // string
    
    // The default Time To Live in seconds, null forever:
    ttl: null, // null|int
);

var_dump($cache instanceof CacheInterface);
// bool(true)

var_dump($cache instanceof CanDeleteExpiredItems);
// bool(true)
```

Check out the [Can Delete Expired Items](#can-delete-expired-items) interface to learn more about it.

### Caches

#### Create Caches

```php
use Tobento\Service\Cache\Simple\Caches;
use Tobento\Service\Cache\Simple\CachesInterface;

$caches = new Caches();

var_dump($caches instanceof CachesInterface);
// bool(true)
```

#### Add Caches

**add**

```php
use Psr\SimpleCache\CacheInterface;

$caches->add(
    name: 'primary',
    cache: $cache, // CacheInterface
);
```

**register**

You may use the register method to only create the cache if requested.

```php
use Psr\SimpleCache\CacheInterface;

$caches->register(
    name: 'name',
    cache: function(string $name): CacheInterface {
        // create the cache:
        return $cache;
    },
);
```

#### Get Cache

If the cache does not exist or could not get created it throws a ```CacheException::class```.

```php
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException;

$cache = $caches->get(name: 'file');

var_dump($cache instanceof CacheInterface);
// bool(true)

$caches->get(name: 'unknown');
// throws CacheException
```

You may use the ```has``` method to check if a cache exists.

```php
var_dump($caches->has('name'));
// bool(false)
```

You may use the ```getNames``` method to get all cache names.

```php
var_dump($caches->getNames());
// array(1) { [0]=> string(4) "file" }
```

#### Default Caches

You may add default caches for your application design.

```php
$caches = new Caches();

// add "file" cache:
$caches->add(name: 'file', cache: $cache);

// add default:
$caches->addDefault(name: 'primary', cache: 'file');

// get default cache for the specified name.
$primaryPool = $caches->default('primary');

var_dump($primaryPool instanceof CacheInterface);
// bool(true)

var_dump($caches->hasDefault('primary'));
// bool(true)

var_dump($caches->getDefaults());
// { ["primary"]=> string(4) "file" }

$caches->default('unknown');
// throws CacheException
```

### Interfaces Simple

#### Cache Factory Interface

You may use this interface for creating caches.

```php
namespace Tobento\Service\Cache\Simple;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException;

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
```

#### Caches Interface

```php
namespace Tobento\Service\Cache\Simple;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\CacheException;

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
```

### Shared Interfaces

#### Can Delete Expired Items

```php
namespace Tobento\Service\Cache;

interface CanDeleteExpiredItems
{
    /**
     * Removes all expired items from the pool or cache.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteExpiredItems(): bool;
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)