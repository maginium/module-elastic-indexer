<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Facades;

use Maginium\ElasticIndexer\Interfaces\IndexerInterface;
use Maginium\ElasticIndexer\Models\IndexerRegistry as IndexerRegistryManager;
use Maginium\Framework\Support\Facade;

/**
 * Class Registry.
 *
 * Facade for interacting with the Registry service, which manages values in the registry.
 *
 * @method static IndexerInterface get(string $indexerId) Retrieve a value by its key, loading lazily if not already registered.
 * @method static void register(string $key, mixed $value, bool $graceful = false) Register a value by key, with graceful option to handle duplicates.
 * @method static bool isRegistered(string $key) Check if a value is registered under a given key.
 * @method static void unregister(string $key) Unregister a value by key, removing it from the registry.
 * @method static IndexerInterface[] getAll() Retrieve all registered data (key-value pairs).
 * @method static void _resetState() Clear all registered data, resetting the registry.
 *
 * @see IndexerRegistryManager
 */
class IndexerRegistry extends Facade
{
    /**
     * Indicates whether resolved instances should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return IndexerRegistryManager::class;
    }
}
