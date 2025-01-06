<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces;

use Maginium\Framework\Support\DataObject;

/**
 * Interface Appendable.
 *
 * Defines the contract for classes that are capable of appending custom data to documents
 * before indexing. The implementing class must provide logic for appending data to documents.
 */
interface AppendableInterface
{
    /**
     * Appends custom data to the document, such as store-specific fields or other metadata.
     *
     * This method allows you to extend the document by adding custom attributes
     * that are necessary for the indexing process, such as store-specific data,
     * additional metadata, or other necessary attributes.
     *
     * @param DataObject $document The document to which custom data will be appended.
     * @param int $storeId The store ID used for context-specific adjustments.
     *
     * @return array The document with appended custom data as an array.
     */
    public function append(DataObject $document, int $storeId): array;
}
