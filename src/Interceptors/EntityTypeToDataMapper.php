<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interceptors;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapperInterface;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Data\IndexInterface;

/**
 * Interceptor to modify the model type context for batch data mapping.
 *
 * This plugin is executed before the `map` method of the BatchDataMapper,
 * setting the `modelType` in the context array if an index identifier is available
 * in the registry. This allows the search engine to use the proper model type
 * metadata when mapping index data.
 *
 * @see DataMapperResolver::map()
 */
class EntityTypeToDataMapper
{
    /**
     * Before method for modifying the context of the map operation.
     *
     * If the index identifier is found in the registry, it sets the `modelType`
     * in the context array. This helps in using the correct metadata for indexing
     * data within the search engine.
     *
     * @param BatchDataMapperInterface $subject The subject class that this plugin is modifying.
     * @param array $documentData The document data being mapped.
     * @param int $storeId The store ID for which the mapping is being performed.
     * @param array $context The context passed to the map method.
     *
     * @return array The modified document data, store ID, and context.
     */
    public function beforeMap(BatchDataMapperInterface $subject, array $documentData, int $storeId, array $context = []): array
    {
        // Retrieve the index identifier from the registry
        $indexIdentifier = IndexerRegistry::get(IndexInterface::IDENTIFIER);

        // If an index identifier exists, set it as the model type in the context
        if ($indexIdentifier) {
            $context['entityType'] = $indexIdentifier;
        }

        // Return the modified document data, store ID, and context
        return [$documentData, $storeId, $context];
    }
}
