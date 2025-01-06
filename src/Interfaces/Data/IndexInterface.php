<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces\Data;

/**
 * Interface for defining the structure of an index.
 *
 * This interface serves as a contract for any class that needs to implement the
 * structure for indexing data in Elasticsearch. It defines constants and methods
 * that should be followed by implementing classes to ensure consistency and
 * compatibility with the indexing logic.
 */
interface IndexInterface
{
    /**
     * The identifier constant for the index.
     *
     * This constant is used to define a standard key for identifying index records
     * within Elasticsearch or similar indexing services. Implementing classes should
     * use this constant to ensure consistency when referencing the identifier field
     * across various components and systems.
     */
    public const IDENTIFIER = 'identifier';
}
