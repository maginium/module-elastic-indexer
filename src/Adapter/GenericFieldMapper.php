<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Adapter;

use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Magento\Indexer\Model\Indexer\DependencyDecorator as IndexerInterface;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Mapping\FieldInterface;
use Maginium\Foundation\Exceptions\LogicException;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Database\Interfaces\SearchableInterface;
use Maginium\Framework\Database\Traits\Searchable;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

/**
 * GenericFieldMapper is responsible for mapping fields for Elasticsearch operations.
 */
class GenericFieldMapper implements FieldMapperInterface
{
    /**
     * Retrieve all attribute types for a given context.
     *
     * @param array<string, mixed> $context Context data for retrieving attribute types.
     *
     * @return array<string, array<string, string>> Associative array of attribute codes with their corresponding types.
     */
    public function getAllAttributesTypes($context = []): array
    {
        // Extract the model type identifier from the context array.
        $modelTypeIdentifier = $context['entityType'] ?? null;

        // Validate that the model type identifier exists and is a string.
        if (! $modelTypeIdentifier || ! Validator::isString($modelTypeIdentifier)) {
            throw LogicException::make('Entity type identifier is missing or invalid in the context.');
        }

        // Attempt to retrieve the index using the model type identifier.
        // First, try fetching it from the index repository. If not found, attempt to retrieve it from the IndexerRegistry.
        /** @var IndexerInterface $index */
        $index = IndexerRegistry::get($modelTypeIdentifier);

        // If the index is not found in either source, throw an exception.
        if (! $index) {
            throw LogicException::make("Index not found for identifier: {$modelTypeIdentifier}");
        }

        // If true, handle logic specific to IndexerRegistry.
        return $this->registry($index, $modelTypeIdentifier);
    }

    /**
     * Retrieve the field name for a given attribute code.
     *
     * @param string $attributeCode The attribute code for which to fetch the field name.
     * @param array<string, mixed> $context Context data for retrieving the field name.
     *
     * @throws LogicException Always throws an exception as this method is not implemented.
     */
    public function getFieldName($attributeCode, $context = []): void
    {
        throw LogicException::make('The method getFieldName is not implemented.');
    }

    /**
     * Handle logic for indexes retrieved via IndexerRegistry.
     *
     * @param IndexerInterface $index Index instance from IndexerRegistry.
     * @param string $modelTypeIdentifier Identifier for the model type.
     *
     * @return array<string, array<string, string>> Attribute types mapped to 'text' by default.
     */
    private function registry(
        IndexerInterface $index,
        string $modelTypeIdentifier,
    ): array {
        // Use the index to resolve the associated action class via a container service.
        // Fetch the complete action and its associated model.
        $indexModel = Container::resolve($index->getActionClass())
            ->getFullAction()
            ->getModel();

        // Check if the resolved model supports search indexing.
        if (! $this->isSearchable($indexModel)) {
            throw LogicException::make("The model for identifier {$modelTypeIdentifier} does not support search indexing.");
        }

        // Retrieve searchable attributes from the model.
        $attributes = $indexModel->getSearchableAttributes();

        // Map the attributes to their types and return the result.
        return $this->mapAttributesToTypes($attributes);
    }

    /**
     * Map attributes to their correct types based on Elasticsearch data types.
     *
     * @param array<string, mixed> $attributes List of attributes.
     *
     * @return array<string, array<string, string>> Attribute types mapped dynamically.
     */
    private function mapAttributesToTypes(array $attributes): array
    {
        $attributeTypes = [];

        foreach ($attributes as $attribute => $data) {
            // Assuming the attribute has a method `getFrontendInput` that returns its type
            $attributeCode = $attribute;
            $attributeType = $data['type'] ?? FieldInterface::TYPE_TEXT; // Default to 'text' if no input type

            // Map to Elasticsearch data types
            $esType = $this->getElasticsearchType($attributeType);

            // Map the attribute code to the corresponding Elasticsearch type
            $attributeTypes[$attributeCode] = ['type' => $esType];
        }

        return $attributeTypes;
    }

    /**
     * Get the Elasticsearch type based on the frontend input type.
     *
     * This method maps a frontend attribute type (e.g., `text`, `date`, etc.) to a
     * corresponding Elasticsearch data type. The mapping ensures that the attribute
     * data is stored in Elasticsearch in the most appropriate format for querying
     * and indexing.
     *
     * @param string $attributeType The frontend input type of the attribute (e.g., `text`, `select`, `price`).
     *
     * @return string The corresponding Elasticsearch data type (e.g., `text`, `keyword`, `date`).
     */
    private function getElasticsearchType(string $attributeType): string
    {
        // Use a switch-case to handle the most common attribute types explicitly.
        switch ($attributeType) {
            case 'text':
            case 'textarea':
                // `text` in Elasticsearch is suitable for full-text search.
                return FieldInterface::TYPE_TEXT;

            case 'select':
            case 'multiselect':
                // `keyword` is used for exact matches and for filtering operations like terms aggregations.
                return FieldInterface::TYPE_KEYWORD;

            case 'date':
                // `date` is used for storing date values in Elasticsearch.
                return FieldInterface::TYPE_DATE;

            case 'boolean':
                // `boolean` stores true/false or 0/1 values.
                return FieldInterface::TYPE_BOOLEAN;

            case 'price':
                // `scaled_float` is often used for numeric fields with decimal precision, like pricing.
                return FieldInterface::TYPE_DOUBLE;

            case 'media_image':
            case 'media_file':
                // Use `keyword` to store media/image paths or identifiers without analyzing.
                return FieldInterface::TYPE_KEYWORD;

            case 'file':
                // `binary` is used to store file data in Elasticsearch.
                return FieldInterface::TYPE_BINARY;

            default:
                // Handle more complex types dynamically, such as arrays or objects.
                if (str_contains($attributeType, 'array')) {
                    // Arrays are often treated as `object` in Elasticsearch for nested fields.
                    return FieldInterface::TYPE_OBJECT;
                }

                if (str_contains($attributeType, 'object')) {
                    // For custom object fields, explicitly use the `object` type.
                    return FieldInterface::TYPE_OBJECT;
                }

                // Fallback type: `text` is used as a generic default for unknown types.
                return FieldInterface::TYPE_TEXT;
        }
    }

    /**
     * Check if the model supports search indexing by verifying it implements the 'SearchableInterface'.
     *
     * @param ModelInterface $model The model to check.
     *
     * @return bool Returns true if the model supports search indexing, false otherwise.
     */
    private function isSearchable(ModelInterface $model): bool
    {
        // Use reflection to check if the model implements the SearchableInterface.
        return Reflection::implements($model, SearchableInterface::class) && Reflection::hasTrait($model, Searchable::class);
    }
}
