<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Consumers;

use Exception;
use Magento\Indexer\Model\Indexer\DependencyDecorator as IndexerInterface;
use Maginium\ElasticIndexer\Enums\ReindexActions;
use Maginium\ElasticIndexer\Facades\IndexerRegistry;
use Maginium\ElasticIndexer\Interfaces\Data\ElasticIndexInterface;
use Maginium\Framework\MessageQueue\Abstracts\AbstractConsumer;
use Maginium\Framework\Support\Facades\Log;

/**
 * Class ElasticIndexerConsumer.
 *
 * Consumer responsible for processing reindexing messages from the 'elastic.indexer'.
 * It handles the reindexing tasks based on the action type (e.g., reindex row, list, all).
 */
class ElasticIndexerConsumer extends AbstractConsumer
{
    /**
     * The name of the queue being consumed.
     *
     * @var string
     */
    protected string $queueName = ElasticIndexInterface::QUEUE_NAME;

    /**
     * The name of the consumer.
     *
     * @var string
     */
    protected string $consumerName = ElasticIndexInterface::CONSUMER_NAME;

    /**
     * Handle the decoded data from the message queue.
     *
     * This method retrieves the necessary data from the message queue,
     * performs reindexing actions, and logs any errors or warnings.
     *
     * @return void
     */
    protected function handle(): void
    {
        try {
            // Retrieve the message data from the queue.
            $data = $this->getData();

            // Extract the indexer instance from the message data.
            $indexerId = $data->getIndexerId();

            // Extract the reindexing action from the message data.
            $action = $data->getAction();

            // Extract the IDs to be reindexed from the message data.
            $ids = $data->getIds();

            // Fetch the index model associated with the searchable identifier
            /** @var IndexerInterface $indexer */
            $indexer = IndexerRegistry::get($indexerId);

            // Process the reindexing based on the action and IDs.
            $this->processReindex($indexer, $ids, $action);
        } catch (Exception $e) {
            // Log any errors encountered during message processing.
            Log::error('Error processing message: ' . $e->getMessage());

            // Rethrow the caught exception to propagate it further.
            throw $e;
        }
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
    private function processReindex(IndexerInterface $indexer, int|array $ids, string $action): void
    {
        // Proceed with reindexing only if the indexer is not already scheduled for reindexing.
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
}
