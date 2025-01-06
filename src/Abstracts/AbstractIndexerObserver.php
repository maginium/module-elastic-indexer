<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Abstracts;

use Maginium\ElasticIndexer\Concerns\HasIndexer;
use Maginium\Foundation\Abstracts\AbstractEntityObserver;

/**
 * Abstract base class for Magento event observers.
 *
 * This class provides the logic for handling events in Magento, including processing
 * events, retrieving event data, validating the data, and dynamically invoking corresponding
 * actions based on event names.
 *
 * @property string $eventPrefix
 */
abstract class AbstractIndexerObserver extends AbstractEntityObserver
{
    // Use the HasIndexer trait to include indexing functionality for brands
    use HasIndexer;
}
