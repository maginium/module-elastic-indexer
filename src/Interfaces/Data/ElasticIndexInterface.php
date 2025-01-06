<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces\Data;

/**
 * Interface for defining the structure of the elastic indexer.
 */
interface ElasticIndexInterface
{
    /**
     * The constant for the queue name.
     *
     * This constant defines the name of the queue used for reindexing operations.
     * Implementing classes should use this constant to ensure that the correct
     * queue is referenced for publishing messages to the queue system.
     */
    public const QUEUE_NAME = 'elastic.indexer';

    /**
     * The constant for the consumer name.
     *
     * This constant defines the name of the consumer responsible for processing
     * reindexing tasks from the queue. Implementing classes should reference this
     * constant when configuring the consumer for processing messages.
     */
    public const CONSUMER_NAME = 'elastic.indexer.consumer';
}
