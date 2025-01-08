<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Abstracts;

use Maginium\ElasticIndexer\Interfaces\DataSourceInterface;
use Maginium\Foundation\Abstracts\DataSource\DataSource as BaseDataSource;
use Maginium\Foundation\Interfaces\DataSourceInterface as AppendableInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Reflection;

/**
 * Abstract class DataSource.
 *
 * This abstract class is responsible for mapping the index data to be used in the search engine's metadata.
 * It provides base functionality for processing and mapping documents before indexing them in the search engine.
 * Concrete classes should implement specific logic for transforming and appending data based on their needs.
 */
abstract class DataSource extends BaseDataSource implements DataSourceInterface
{
    /**
     * Maps the provided document data to be used for indexing in the search engine.
     *
     * This method processes the raw document data by transforming it into a format compatible with the search engine's indexing system.
     * It enriches or modifies the data before it is indexed, including adding store-specific fields or handling arrays.
     *
     * @param array $documentData The document data to be mapped (e.g., an array of product data).
     * @param int $storeId The store ID for context-specific adjustments.
     * @param array $context Additional contextual information for mapping, can be extended as needed.
     *
     * @return array The mapped data, formatted for indexing in the search engine.
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        // Iterate over each document in the provided data
        // Convert the document array to a DataObject for easier manipulation
        $document = DataObject::make($documentData);

        // Process the document by transforming and appending necessary data
        $documentData = $this->processDocument($document, $storeId);

        // Return the final mapped document data
        return $documentData;
    }

    /**
     * Appends custom data to the document.
     *
     * This method appends additional store-specific fields or other custom data to the document,
     * using the `append` method if it's implemented in the concrete class.
     *
     * @param DataObject $document The document data to which custom data will be appended.
     * @param int $storeId The store ID for context-specific adjustments.
     *
     * @return mixed The appended data
     */
    private function processDocument(DataObject $document, int $storeId): mixed
    {
        // Check if the class implements AppendableInterface
        if (! Reflection::implements(static::class, AppendableInterface::class)) {
            return null;
        }

        // Call the `append` method and passing both the document and the store id
        return $this->addData($document, $storeId);
    }
}
