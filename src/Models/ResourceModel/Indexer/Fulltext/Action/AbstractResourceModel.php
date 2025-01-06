<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Models\ResourceModel\Indexer\Fulltext\Action;

use Magento\Eav\Model\Entity\Collection\AbstractCollection as EavCollection;
use Magento\Sales\Model\ResourceModel\Order\Collection\AbstractCollection as SalesCollection;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Class AbstractResourceModel.
 *
 * Provides common functionality for resource models, including
 * indexing capabilities and collection handling.
 */
abstract class AbstractResourceModel
{
    /**
     * Factory used to create instances of model collections.
     *
     * @var object
     */
    private object $collectionFactory;

    /**
     * Represents the model model, providing searchable attributes and metadata.
     *
     * @var object
     */
    private object $model;

    /**
     * Constructor.
     *
     * Initializes dependencies needed for the Index class.
     *
     * @param mixed $collectionFactory Factory for creating model collection instances.
     * @param mixed $model Implements search-related methods specific to the model model.
     */
    public function __construct(
        $model,
        $collectionFactory,
    ) {
        $this->model = $model;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get the model model instance.
     *
     * Provides direct access to the model instance, useful for operations requiring model-specific logic.
     *
     * @return ModelInterface The model model instance.
     */
    public function getModel(): ModelInterface
    {
        return $this->model->create();
    }

    /**
     * Get a new instance of the model collection.
     *
     * Allows dynamic creation of collections, enabling filtering and pagination operations.
     *
     * @return EavCollection|SalesCollection A new model collection instance.
     */
    public function getCollection(): mixed
    {
        return $this->collectionFactory->create();
    }

    /**
     * Retrieve indexable documents for the index.
     *
     * Fetches a collection of models filtered by various parameters
     * (e.g., model ID, model IDs, pagination limits) and prepares them for indexing.
     *
     * @param int $storeId The store ID to filter results by.
     * @param array|null $modelIds Optional array of model IDs to include in the results.
     * @param int|null $lastEntityId Optional ID of the last processed model (used for pagination).
     * @param int $limit Maximum number of documents to retrieve (default is 100).
     *
     * @return array The array of indexable documents, ready for processing.
     */
    abstract public function getIndexableDocuments(
        int $storeId,
        ?array $modelIds = null,
        ?int $lastEntityId = null,
        int $limit = 100,
    ): array;
}
