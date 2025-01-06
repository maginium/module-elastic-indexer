<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Adapter;

use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Request\BucketInterface;

/**
 * GenericAggregationDataProvider provides stub methods for handling aggregations.
 *
 * This class is a placeholder for most indexes that don't support aggregations, but still need
 * to have a provider registered to fulfill the interface contract.
 */
class GenericAggregationDataProvider implements DataProviderInterface
{
    /**
     * Retrieves the range for aggregation.
     * As most indexes do not support aggregations, it returns 0.
     *
     * @return int The range value, always 0 in this case.
     */
    public function getRange()
    {
        return 0;
    }

    /**
     * Returns the aggregations for the specified model storage.
     * Since this is a generic stub, it returns an empty array.
     *
     * @param EntityStorage $modelStorage The model storage object containing the data.
     *
     * @return array An empty array as aggregations are not supported in this provider.
     */
    public function getAggregations(EntityStorage $modelStorage)
    {
        return [];
    }

    /**
     * Prepares the interval for a given bucket, dimensions, and model storage.
     * As this is a generic implementation, the method does nothing.
     *
     * @param BucketInterface $bucket The bucket for the aggregation.
     * @param array $dimensions The dimensions of the aggregation.
     * @param EntityStorage $modelStorage The model storage object.
     *
     * @return void
     */
    public function getInterval(
        BucketInterface $bucket,
        array $dimensions,
        EntityStorage $modelStorage,
    ): void {
    }

    /**
     * Retrieves the aggregation for the specified bucket, dimensions, and range.
     * Since this is a generic implementation, it returns an empty array.
     *
     * @param BucketInterface $bucket The bucket for the aggregation.
     * @param array $dimensions The dimensions of the aggregation.
     * @param mixed $range The range for the aggregation.
     * @param EntityStorage $modelStorage The model storage object.
     *
     * @return array An empty array as no aggregation is provided.
     */
    public function getAggregation(
        BucketInterface $bucket,
        array $dimensions,
        $range,
        EntityStorage $modelStorage,
    ) {
        return [];
    }

    /**
     * Prepares data for aggregation based on the range and database ranges.
     * Returns an empty array since no actual data is processed in this stub.
     *
     * @param mixed $range The range for the aggregation.
     * @param array $dbRanges The database ranges for the aggregation.
     *
     * @return array An empty array as no data preparation is done.
     */
    public function prepareData($range, array $dbRanges)
    {
        return [];
    }
}
