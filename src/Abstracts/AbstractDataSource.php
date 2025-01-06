<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Abstracts;

use Maginium\ElasticIndexer\Interfaces\AppendableInterface;
use Maginium\ElasticIndexer\Interfaces\DataSourceInterface;
use Maginium\ElasticIndexer\Interfaces\TransformableInterface;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

/**
 * Abstract class AbstractDataSource.
 *
 * This abstract class is responsible for mapping the index data to be used in the search engine's metadata.
 * It provides base functionality for processing and mapping documents before indexing them in the search engine.
 * Concrete classes should implement specific logic for transforming and appending data based on their needs.
 *
 * @method mixed transform(mixed $item) Transforms the individual item for indexing.
 * @method array append(DataObject $document, int $storeId) Appends custom data to the document.
 */
abstract class AbstractDataSource implements DataSourceInterface
{
    /**
     * Sort order for the model data.
     *
     * @var int
     */
    public static int $sortOrder = 0;

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
        foreach ($documentData as $id => $document) {
            // Process the document by transforming and appending necessary data
            $documentData[$id] = $this->processDocument($document, $storeId);
        }

        // Return the final mapped document data
        return $documentData;
    }

    /**
     * Transforms a single document by updating its attribute values.
     *
     * This method calls the `transform` method (if implemented) for each attribute of the document.
     * It ensures that each attribute is transformed before being returned.
     *
     * @param array $document The document data to be transformed.
     *
     * @return array The transformed document with updated attribute values.
     */
    protected function transformDocument(array $document): array
    {
        // Iterate over each attribute in the document
        foreach ($document as $attribute => $value) {
            // If the 'transform' method exists, transform the value
            if (Reflection::implements(static::class, TransformableInterface::class)) {
                $document[$attribute] = $this->transform($value);
            }
        }

        return $document;
    }

    /**
     * Appends custom data to the document.
     *
     * This method appends additional store-specific fields or other custom data to the document,
     * using the `append` method if it's implemented in the concrete class.
     *
     * @param array $document The document data to which custom data will be appended.
     * @param int $storeId The store ID for context-specific adjustments.
     *
     * @return array The document with appended custom data.
     */
    protected function appendToDocument(array $document, int $storeId): array
    {
        // Convert the document array to a DataObject for easier manipulation
        $documentObject = DataObject::make($document);

        // Call the `append` method if it exists to append custom data
        if (Reflection::implements(static::class, AppendableInterface::class)) {
            $document = $this->append($documentObject, $storeId);
        }

        return $document;
    }

    /**
     * Processes a single document by transforming and appending necessary data.
     *
     * This method first transforms the document using the `transformDocument` method,
     * and then appends custom data using the `appendToDocument` method.
     *
     * @param array $document The document data to be processed.
     * @param int $storeId The store ID for the document, used in the append method.
     *
     * @return array The processed document with transformed and appended data.
     */
    private function processDocument(array $document, int $storeId): array
    {
        // First, transform the document's data
        $document = $this->transformDocument($document);

        // If the document is empty after transformation, return the original document
        if (Validator::isEmpty($document)) {
            return $document;
        }

        // Append custom data to the document
        $document = $this->appendToDocument($document, $storeId);

        // If the document is empty after appending, return the original document
        if (Validator::isEmpty($document)) {
            return $document;
        }

        return $document;
    }
}
