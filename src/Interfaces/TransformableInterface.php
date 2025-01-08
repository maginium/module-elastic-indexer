<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces;

/**
 * Interface TransformableInterface.
 *
 * Defines a contract for classes that handle transformation of individual attributes
 * for indexing purposes. Implementing classes must provide logic to process and
 * transform attributes into the desired format.
 */
interface TransformableInterface
{
    /**
     * Transforms an attribute's value for indexing.
     *
     * Implement this method to apply transformation logic to individual attributes.
     * For example, this can include formatting dates, normalizing text, or converting
     * data types to match the indexing requirements. The transformation can be influenced
     * by the store context.
     *
     * @param string $attribute The name of the attribute being transformed.
     * @param mixed $value The current value of the attribute to be transformed.
     * @param int|null $storeId The ID of the store context, if applicable.
     *
     * @return mixed The transformed value ready for indexing.
     */
    public function transform(string $attribute, mixed $value, ?int $storeId = null): mixed;
}
