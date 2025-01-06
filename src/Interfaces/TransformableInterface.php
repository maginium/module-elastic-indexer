<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces;

/**
 * Interface Transformable.
 *
 * Defines the contract for classes that are capable of transforming items before indexing.
 * The implementing class must provide logic for transforming individual items.
 */
interface TransformableInterface
{
    /**
     * Transforms the individual item for indexing.
     *
     * This method is responsible for transforming each individual item (attribute)
     * into a suitable format for indexing. For example, it could convert dates into
     * a specific format, modify string fields, or perform any other transformation logic.
     *
     * @param mixed $item The item to be transformed.
     *
     * @return mixed The transformed item.
     */
    public function transform(mixed $item): mixed;
}
