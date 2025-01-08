<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Datasource;

use Magento\Elasticsearch\Model\Config;
use Maginium\ElasticIndexer\Abstracts\DataSource;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Data\IndexInterface;
use Maginium\ElasticIndexer\Interfaces\DataSourceInterface;
use Maginium\Foundation\Abstracts\DataSource\DataSourceResolver;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Override;

/**
 * Resolver class responsible for mapping document data using data sources.
 * This class identifies the appropriate data sources for a given entity type,
 * validates them, and processes them to map document data.
 */
class Resolver extends DataSourceResolver
{
    /**
     * Key used to identify entity type in the context array.
     */
    public const ENTITY_TYPE = 'entityType';

    /**
     * Constructor method.
     * Initializes the resolver with the provided data sources registry.
     *
     * @param Registry $registry The registry used to manage data sources.
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry);
    }

    /**
     * Map the provided document data using applicable data sources for the given entity type.
     *
     * @param array $documentData The data to be mapped.
     * @param int|string $storeId The store ID.
     * @param array $context Additional context for the mapping.
     *
     * @return array The mapped data combined from all sources.
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        // Checking if the index identifier exists on context
        $indexIdentifier = isset($context[self::ENTITY_TYPE]) ?? $context[self::ENTITY_TYPE];

        // Resolve the model type based on the index identifier, entity type from the context, or a default value.
        $modelType = $this->resolveModelType($indexIdentifier);

        // Retrieve all data sources registered for the resolved model type.
        $dataSources = $this->registry->getDatasourcesForEntity($modelType);

        // If no data sources are available, return the original document data unchanged.
        if (Validator::isEmpty($dataSources)) {
            return $documentData;
        }

        // Iterate over each document in the provided data
        foreach ($documentData as $id => $document) {
            // Creating a DataObject from the response data.
            $documentDataObject = DataObject::make($document);

            // Process the data sources concurrently and collect the results.
            $results[$id] = $this->processDataSources($dataSources, $documentDataObject, (int)$storeId);
        }

        // Merge all the results into a single array and return the final mapped data.
        return $results;
    }

    /**
     * Prepares tasks for concurrent execution.
     *
     * @param DataSource[] $dataSources The data sources to process.
     * @param DataObject $documentData The document data to be processed.
     * @param int $storeId The store ID.
     *
     * @return callable[] An array of callables representing tasks.
     */
    #[Override]
    protected function prepareTasks(array $dataSources, DataObject $documentData, int $storeId): array
    {
        // Use a collection to map each data source to a callable task.
        return collect($dataSources)->map(
            fn(DataSource $source): callable => function() use ($source, $documentData, $storeId) {
                // Validate that the data source implements the required interface.
                $this->validateDataSource($source);

                // Call the addData method on the data source with the document data and store ID.
                return $source->map($documentData->toArray(), $storeId);
            },
        )->toArray();
    }

    /**
     * Validates that the data source implements the required interface.
     *
     * @param mixed $source The data source to validate.
     *
     * @throws InvalidArgumentException If the data source does not implement the required interface.
     *
     * @return void
     */
    #[Override]
    protected function validateDataSource($source): void
    {
        // Check if the data source implements the required interface.
        if (! Reflection::implements($source, DataSourceInterface::class)) {
            // Throw an exception if the data source does not implement the interface.
            throw InvalidArgumentException::make(
                Str::format('The data source "%s" must implement the interface "%s".', get_class($source), DataSourceInterface::class),
            );
        }
    }

    /**
     * Resolves the entity type based on the context or default configuration.
     *
     * @param string $entity The entity class.
     *
     * @return string The resolved entity type, which determines the applicable data sources.
     */
    #[Override]
    protected function resolveModelType($entity): string
    {
        // Attempt to retrieve the index identifier from the registry.
        $indexIdentifier = IndexerRegistry::get(IndexInterface::IDENTIFIER);

        // Return the index identifier if available; otherwise, use the entity type from the context or a default value.
        return $indexIdentifier ?? $entity ?? Config::ELASTICSEARCH_TYPE_DEFAULT;
    }
}
