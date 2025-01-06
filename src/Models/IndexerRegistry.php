<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Models;

use InvalidArgumentException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterface\Proxy as IndexerInterfaceProxy;
use Magento\Framework\Indexer\IndexerRegistry as BaseIndexerRegistry;
use Maginium\Framework\Support\Collection;
use RuntimeException;

/**
 * Registry Class to manage the registration and retrieval of indexers.
 */
class IndexerRegistry extends BaseIndexerRegistry
{
    /**
     * Registered indexers identified by a key.
     *
     * @var Collection
     */
    protected Collection $_indexers;

    /**
     * Instance of IndexerInterface to load indexers when needed.
     *
     * @var IndexerInterface
     */
    protected IndexerInterfaceProxy $indexer;

    /**
     * Constructor to initialize the Registry with a given IndexerInterface.
     *
     * @param IndexerInterface $indexer The indexer to load when needed.
     */
    public function __construct(IndexerInterfaceProxy $indexer)
    {
        $this->indexer = $indexer;

        // Initialize as a Collection
        $this->_indexers = Collection::make();
    }

    /**
     * Retrieve a value by its key, loading lazily if not already registered.
     *
     * @param string $indexerId The key under which the value is registered.
     *
     * @return mixed The value registered under the given key.
     */
    public function get($indexerId): mixed
    {
        // Lazy load the value if it is not already registered
        return $this->_indexers->get($indexerId, fn() => $this->indexer->load($indexerId));
    }

    /**
     * Register a value by key, with graceful option to handle duplicates.
     *
     * @param string $key The key to register the value under.
     * @param mixed $value The value to register.
     * @param bool $graceful Option to handle duplicate keys gracefully.
     *
     * @throws InvalidArgumentException If the key is already registered (when $graceful is false).
     *
     * @return void
     */
    public function register(string $key, mixed $value, bool $graceful = false): void
    {
        if ($this->_indexers->has($key)) {
            if ($graceful) {
                return;
            }

            throw new RuntimeException('Registry key "' . $key . '" already exists');
        }

        // Register the value under the given key
        $this->_indexers->put($key, $value);
    }

    /**
     * Check if a value is registered under a given key.
     *
     * @param string $key The key to check.
     *
     * @return bool True if the key is registered, false otherwise.
     */
    public function isRegistered(string $key): bool
    {
        return $this->_indexers->has($key);
    }

    /**
     * Unregister a value by key, removing it from the registry.
     *
     * @param string $key The key to unregister.
     *
     * @return void
     */
    public function unregister(string $key): void
    {
        $this->_indexers->forget($key);
    }

    /**
     * Retrieve all registered data (key-value pairs).
     *
     * @return array An associative array of all registered key-value pairs.
     */
    public function getAll(): array
    {
        return $this->_indexers->toArray();
    }

    /**
     * Clear all registered data, resetting the registry.
     *
     * @return void
     */
    public function _resetState(): void
    {
        // Reset Collection
        $this->_indexers = Collection::make();
    }

    /**
     * Destruct registry items, ensuring all indexers are unregistered upon destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->_indexers->each(fn($key) => $this->unregister($key));
    }
}
