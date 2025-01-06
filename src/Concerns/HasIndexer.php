<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Concerns;

use Magento\Indexer\Model\Indexer\DependencyDecorator as IndexerInterface;
use Maginium\ElasticIndexer\Enums\ReindexActions;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Data\ElasticIndexInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Database\Interfaces\SearchableInterface;
use Maginium\Framework\Database\Traits\Searchable;
use Maginium\Framework\Support\DataObject;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Publisher;
use Maginium\Framework\Support\Reflection;

/**
 * Trait HasIndexer.
 *
 * This trait handles the reindexing of models based on changes to product-related events.
 * It provides functionality for reindexing actions such as updating, deleting, and reindexing by ID or row.
 * It ensures that the model's searchable model is properly indexed in the search system.
 *
 * @property DataObject|null $dataObject The model object related to the model being indexed.
 * @property Model|null $model The model class that handles the event-related data processing.
 */
trait HasIndexer
{
    /**
     * Perform reindexing based on specified model IDs and action type.
     *
     * This method handles reindexing operations for models. It supports various action types including:
     * - 'row': Reindex a specific row identified by its ID.
     * - 'list': Reindex all rows.
     * - 'ids': Reindex rows by a list of IDs.
     * - 'delete': Perform a delete reindex operation (if supported).
     *
     * @param int[]|null $ids The list of model IDs to reindex. This is optional and only required for actions like 'ids' or 'row'.
     * @param string $action The reindexing action to perform (row, list, ids, delete).
     *
     * @throws Exception If an error occurs during the reindexing process.
     *
     * @return void
     */
    public function reindex(?array $ids = null, string $action = ReindexActions::ACTION_ROW): void
    {
        try {
            // Check if the model is searchable by verifying if it uses the 'Searchable' trait
            if ($this->isSearchable()) {
                // Fetch the index model associated with the searchable identifier
                /** @var IndexerInterface $indexer */
                $indexer = IndexerRegistry::get($this->model->indexableAs());

                // Determine the ID to be reindexed based on the action type
                $id = $this->determineIdToReindex($ids, $action);

                // Dispatch the reindexing task to the queue
                $this->performQueueReindexing($indexer, $id, $action);
            }
        } catch (Exception $e) {
            // Log any errors encountered during the reindexing process
            Log::error('Error in reindex: ' . $e->getMessage());
        }
    }

    /**
     * Check if the model supports search indexing by checking if it uses the 'Searchable' trait.
     *
     * @return bool Returns true if the model has the 'Searchable' trait, false otherwise.
     */
    private function isSearchable(): bool
    {
        // Use reflection to check if the model has the 'Searchable' trait
        return Reflection::implements($this->model, SearchableInterface::class) && Reflection::hasTrait($this->model, Searchable::class);
    }

    /**
     * Determine the ID to reindex based on the action type.
     *
     * This method determines which ID should be used for reindexing depending on the action type.
     * If the action is 'row', it uses the first element of the $ids array, otherwise, it defaults to the model's ID.
     *
     * @param int[]|null $ids The list of model IDs to reindex.
     * @param string $action The reindexing action to perform (row, list, ids, delete).
     *
     * @return int The ID to reindex.
     */
    private function determineIdToReindex(?array $ids, string $action): int
    {
        // For 'row' action, use the first element of the provided $ids array.
        // Otherwise, use the model's ID as the reindexing target.
        return ($action === ReindexActions::ACTION_ROW) ? (int)reset($ids) : (int)$this->data->getData($this->model->getKeyName());
    }

    /**
     * Perform reindexing based on the specified action.
     *
     * This method determines the reindexing action to take based on the provided action string.
     * It supports reindexing a specific row, a list of rows, or all rows. It also handles unsupported actions
     * by logging a warning.
     *
     * @param IndexerInterface $indexer The indexer instance to perform the reindexing.
     * @param int|array $ids The ID(s) to be reindexed. Can be a single ID or an array of IDs.
     * @param string $action The reindexing action to perform. Must be one of the defined actions such as 'row', 'ids', or 'list'.
     *
     * @return void
     */
    private function performReindexing(IndexerInterface $indexer, int|array $ids, string $action): void
    {
        if (! $indexer->isScheduled()) {
            // Use a match expression to handle the different reindex actions
            match ($action) {
                ReindexActions::ACTION_ROW => $indexer->reindexRow($ids), // Reindex a specific row by ID
                ReindexActions::ACTION_IDS => $indexer->reindexList($ids), // Reindex a list of rows by ID
                ReindexActions::ACTION_LIST => $indexer->reindexAll(), // Reindex all rows or list of rows
                default => Log::warning("Unsupported reindex action: {$action}"), // Log a warning for unsupported actions
            };
        }
    }

    /**
     * Dispatch reindexing operation to the queue.
     *
     * This method dispatches the reindexing operation to the message queue, sending the index instance,
     * IDs, and the action to perform as part of the message payload. The message will be consumed and processed
     * by a consumer to perform the actual reindexing.
     *
     * @param IndexerInterface $indexer The index instance to reindex.
     * @param int|array $ids The ID(s) to be reindexed. Can be a single ID or an array of IDs.
     * @param string $action The reindexing action to perform. Valid actions include 'row', 'ids', 'list'.
     *
     * @return void
     */
    private function performQueueReindexing(IndexerInterface $indexer, int|array $ids, string $action): void
    {
        Publisher::dispatch(
            ElasticIndexInterface::QUEUE_NAME, // Event name for the reindexing queue
            [
                'ids' => $ids, // The ID(s) to reindex
                'action' => $action, // The action to perform (row, list, ids, delete)
                'indexer_id' => $indexer->getId(), // The index instance to be reindexed
            ],
        );
    }
}
