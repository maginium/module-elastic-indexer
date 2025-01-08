<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Models\Indexer\Fulltext\Action;

use InvalidArgumentException;
use LogicException;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Maginium\ElasticIndexer\Models\ResourceModel\Indexer\Fulltext\Action\AbstractResourceModel;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Traversable;

/**
 * Class AbstractAction.
 *
 * Provides functionality to rebuild the store index for model-related data.
 * It interacts with the resource model to fetch indexable documents and processes them incrementally.
 */
class AbstractAction
{
    /**
     * Standard identifier key used in model data arrays.
     */
    public const ID = 'id';

    /**
     * Resource model for fetching and indexing data.
     *
     * @var AbstractResourceModel
     */
    private AbstractResourceModel $resourceModel;

    /**
     * Handles Magento's area-specific configurations.
     *
     * @var AreaList
     */
    private AreaList $areaList;

    /**
     * Constructor.
     *
     * Initializes dependencies required for rebuilding the store index.
     *
     * @param AreaList $areaList Manages Magento application areas (e.g., frontend, adminhtml).
     * @param AbstractResourceModel $resourceModel Provides access to the resource model for indexing.
     */
    public function __construct(
        AreaList $areaList,
        AbstractResourceModel $resourceModel,
    ) {
        $this->areaList = $areaList;
        $this->resourceModel = $resourceModel;
    }

    /**
     * Retrieve the model model.
     *
     * Provides access to the ModelInterface instance for interacting with model data.
     *
     * @return ModelInterface
     */
    public function getModel(): ModelInterface
    {
        return $this->resourceModel->getModel();
    }

    /**
     * Rebuild the store index.
     *
     * Processes a list of models (or all models if no IDs are provided) for a specific store,
     * fetching their data in batches and yielding it for further processing.
     *
     * @param int $storeId The store ID for which the index is being rebuilt.
     * @param array|null $modelIds Optional list of model IDs to process. Defaults to null (all models).
     *
     * @return Traversable Yields indexed model data as key-value pairs (ID => data array).
     */
    public function rebuildStoreIndex(int $storeId, ?array $modelIds = null): Traversable
    {
        // Initialize the last processed model ID for incremental fetching.
        $lastEntityId = 0;

        try {
            // Ensure the frontend area's design configuration is loaded for consistency.
            $this->areaList->getArea(Area::AREA_FRONTEND)->load(Area::PART_DESIGN);
        } catch (InvalidArgumentException|LogicException $exception) {
            // Catch any exceptions related to area loading, especially during full reindex scenarios.
            // Magento sample data or misconfigured areas might trigger these exceptions.
        }

        do {
            // Fetch a batch of indexable documents based on the store ID and filters.
            $models = $this->resourceModel->getIndexableDocuments($storeId, $modelIds, $lastEntityId);

            foreach ($models as $modelData) {
                // Get the primary key name for the model.
                $primaryKey = $this->getModel()->getKeyName();

                // Get the lastEntityId using the new method.
                $lastEntityId = $this->getLastEntityId($modelData, $primaryKey);

                // Ensure the model data includes an 'id' key (fallback to primaryKey or static::ID if needed).
                $modelData[static::ID] ??= (int)$modelData[$primaryKey];

                // Unset the original primary key after extracting the ID.
                unset($modelData[$primaryKey]);

                // Yield the current model's data, allowing incremental processing.
                yield $lastEntityId => $modelData;
            }
        } while (! empty($models)); // Continue fetching until no more models are available.
    }

    /**
     * Get the last entity ID from the model data.
     *
     * Attempts to retrieve the ID from either the primary key or static::ID if the primary key is missing.
     *
     * @param array $modelData The model data.
     * @param string $primaryKey The name of the primary key.
     *
     * @return int The last entity ID.
     */
    private function getLastEntityId(array $modelData, string $primaryKey): int
    {
        // Try to get the lastEntityId from the primary key or fall back to static::ID.
        return isset($modelData[$primaryKey])
            ? (int)$modelData[$primaryKey]
            : (int)($modelData[static::ID] ?? 0);
    }
}
