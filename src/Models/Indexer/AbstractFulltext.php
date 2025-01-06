<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Models\Indexer;

use ArrayObject;
use Magento\CatalogSearch\Model\Indexer\IndexerHandlerFactory;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\ConfigInterface as IndexerConfigInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\Search\Request\DimensionFactory;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Data\IndexInterface;
use Maginium\ElasticIndexer\Models\Indexer\Fulltext\Action\AbstractAction;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\StoreManager;

/**
 * AbstractFulltext.
 *
 * Handles full-text indexing operations, integrating Magento's indexing system with custom actions.
 */
class AbstractFulltext implements ActionInterface, MviewActionInterface
{
    /**
     * The indexer handler for managing index operations.
     *
     * @var IndexerInterface
     */
    private IndexerInterface $indexerHandler;

    /**
     * Creates dimensions for scoping index operations.
     *
     * @var DimensionFactory
     */
    private DimensionFactory $dimensionFactory;

    /**
     * Executes full indexing logic for a store.
     *
     * @var AbstractAction
     */
    private AbstractAction $fullAction;

    /**
     * The index identifier used for the current indexing process.
     *
     * @var string
     */
    private string $indexableId;

    /**
     * Constructor.
     *
     * Initializes required dependencies for full-text indexing.
     *
     * @param AbstractAction $fullAction Executes store-specific indexing actions.
     * @param DimensionFactory $dimensionFactory Creates dimension instances for index operations.
     * @param IndexerConfigInterface $indexerConfig Provides configuration for the indexer.
     * @param IndexerHandlerFactory $indexHandlerFactory Creates index handler instances.
     */
    public function __construct(
        AbstractAction $fullAction,
        DimensionFactory $dimensionFactory,
        IndexerConfigInterface $indexerConfig,
        IndexerHandlerFactory $indexHandlerFactory,
    ) {
        $this->fullAction = $fullAction;
        $this->dimensionFactory = $dimensionFactory;

        // Retrieve and cache the indexable ID
        $this->indexableId = $this->getFullAction()->getModel()->indexableAs();

        // Retrieve indexer configuration data and initialize the indexer handler.
        $configData = $indexerConfig->getIndexer($this->indexableId);
        $this->indexerHandler = $indexHandlerFactory->create(['data' => $configData]);
    }

    /**
     * Execute partial reindexing for the given model IDs.
     *
     * @param int[] $ids The IDs of models to reindex.
     *
     * @return void
     */
    public function execute($ids): void
    {
        // Clears and registers the current index for processing a specific row.
        $this->registerCurrentIndex();

        // Retrieve all store IDs to perform index operations for each store.
        $storeIds = Arr::keys(StoreManager::getStores());

        foreach ($storeIds as $storeId) {
            // Create a dimension for the current store scope.
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);

            // Remove existing index entries for the given model IDs in the current store scope.
            $this->indexerHandler->deleteIndex([$dimension], new ArrayObject($ids));

            // Rebuild and save index entries for the given model IDs in the current store scope.
            $this->indexerHandler->saveIndex(
                [$dimension],
                $this->fullAction->rebuildStoreIndex($storeId, $ids),
            );
        }
    }

    /**
     * Retrieve the full action instance.
     *
     * Provides access to the AbstractAction responsible for executing full indexing logic.
     *
     * @return AbstractAction
     */
    public function getFullAction(): AbstractAction
    {
        return $this->fullAction;
    }

    /**
     * Execute full reindexing for all models across all stores.
     *
     * @return void
     */
    public function executeFull(): void
    {
        // Clears and registers the current index for processing a specific row.
        $this->registerCurrentIndex();

        // Retrieve all store IDs for full indexation.
        $storeIds = Arr::keys(StoreManager::getStores());

        foreach ($storeIds as $storeId) {
            // Create a dimension for the current store scope.
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);

            // Clean existing index entries for the current store scope.
            $this->indexerHandler->cleanIndex([$dimension]);

            // Rebuild and save all index entries for the current store scope.
            $this->indexerHandler->saveIndex(
                [$dimension],
                $this->fullAction->rebuildStoreIndex($storeId),
            );
        }
    }

    /**
     * Execute partial reindexing for a list of model IDs.
     *
     * @param int[] $ids The IDs of models to reindex.
     *
     * @return void
     */
    public function executeList(array $ids): void
    {
        $this->execute($ids);
    }

    /**
     * Execute reindexing for a single model ID.
     *
     * @param int $id The ID of the model to reindex.
     *
     * @return void
     */
    public function executeRow($id): void
    {
        $this->execute([$id]);
    }

    /**
     * Clears and registers the current index for processing a specific row.
     *
     * This method unregisters the previous index identifier and registers the current one
     * to ensure the correct index is used for field and data mappings.
     *
     * @return void
     */
    public function registerCurrentIndex(): void
    {
        // Unregister any previously registered index identifier
        IndexerRegistry::unregister(IndexInterface::IDENTIFIER);

        // Register the current index identifier for processing the row
        IndexerRegistry::register(IndexInterface::IDENTIFIER, $this->indexableId, true);
    }
}
