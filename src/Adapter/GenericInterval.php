<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Adapter;

use Magento\Framework\DB\Select;
use Magento\Framework\Search\Dynamic\IntervalInterface;
use Maginium\Framework\Support\Arr;

/**
 * Class representing a MySQL search aggregation interval.
 *
 * This class is responsible for handling the interval logic of search aggregations,
 * providing methods to load previous and next intervals of data in a search query.
 * It interacts with the database via the Select object to retrieve and process
 * interval data, adjusting the limits, offsets, and filters accordingly.
 */
class GenericInterval implements IntervalInterface
{
    /**
     * Minimal possible value used for delta adjustments in range queries.
     *
     * @var float
     */
    public const DELTA = 0.005;

    /**
     * The select query object used for database interactions.
     *
     * @var Select
     */
    private $select;

    /**
     * Constructor for the GenericInterval class.
     *
     * Initializes the select query object used for retrieving data from the database.
     *
     * @param Select $select The select query object.
     */
    public function __construct(Select $select)
    {
        $this->select = $select;
    }

    /**
     * Load data for the given interval with the specified limits and offsets.
     *
     * This method retrieves data within a specified range, applying the lower and upper bounds
     * and adjusting the range using the DELTA constant to avoid boundary issues.
     *
     * @param int|null $limit The maximum number of results to return.
     * @param int|null $offset The offset from which to start fetching data.
     * @param float|null $lower The lower bound of the range.
     * @param float|null $upper The upper bound of the range.
     *
     * @return array The resulting data, converted to an array of floats.
     */
    public function load($limit, $offset = null, $lower = null, $upper = null)
    {
        // Clone the original select object to avoid modifying it directly
        $select = clone $this->select;
        $value = $this->getValueFiled();

        // Apply the lower bound filter if provided
        if ($lower !== null) {
            $select->where("{$value} >= ?", $lower - self::DELTA);
        }

        // Apply the upper bound filter if provided
        if ($upper !== null) {
            $select->where("{$value} < ?", $upper - self::DELTA);
        }

        // Set the order and limits for the query
        $select->order('value ASC')->limit($limit, $offset);

        // Execute the query and convert the results to an array of floats
        return $this->arrayValuesToFloat(
            $this->select->getConnection()->fetchCol($select),
        );
    }

    /**
     * Load the previous interval of data relative to a given value.
     *
     * This method loads data for the previous interval based on a provided data point and index.
     * It calculates the offset and applies the necessary filters for the previous range.
     *
     * @param float $data The current data point to find the previous interval relative to.
     * @param int $index The current index in the range.
     * @param float|null $lower The lower bound of the range.
     *
     * @return array|bool The previous interval's data or false if no previous data is found.
     */
    public function loadPrevious($data, $index, $lower = null)
    {
        // Clone the select object for the query
        $select = clone $this->select;
        $value = $this->getValueFiled();

        // Set the query to count the number of rows for the previous interval
        $select->columns(['count' => 'COUNT(*)'])->where("{$value} < ?", $data - self::DELTA);

        // Apply the lower bound filter if provided
        if ($lower !== null) {
            $select->where("{$value} >= ?", $lower - self::DELTA);
        }

        // Fetch the count of rows for the previous interval
        $offset = $this->select->getConnection()->fetchRow($select)['count'];

        // Return false if no data is found in the previous interval
        if (! $offset) {
            return false;
        }

        // Load the previous data by adjusting the index and offset
        return $this->load($index - $offset + 1, $offset - 1, $lower);
    }

    /**
     * Load the next interval of data relative to a given value.
     *
     * This method loads data for the next interval based on a provided data point and index.
     * It calculates the offset and applies the necessary filters for the next range.
     *
     * @param float $data The current data point to find the next interval relative to.
     * @param int $rightIndex The current right index in the range.
     * @param float|null $upper The upper bound of the range.
     *
     * @return array|bool The next interval's data or false if no next data is found.
     */
    public function loadNext($data, $rightIndex, $upper = null)
    {
        // Clone the select object for the query
        $select = clone $this->select;
        $value = $this->getValueFiled();

        // Set the query to count the number of rows for the next interval
        $select->columns(['count' => 'COUNT(*)'])->where("{$value} > ?", $data + self::DELTA);

        // Apply the upper bound filter if provided
        if ($upper !== null) {
            $select->where("{$value} < ?", $data - self::DELTA);
        }

        // Fetch the count of rows for the next interval
        $offset = $this->select->getConnection()->fetchRow($select)['count'];

        // Return false if no data is found in the next interval
        if (! $offset) {
            return false;
        }

        // Load the next data by adjusting the range and order
        $select = clone $this->select;
        $select->where("{$value} >= ?", $data - self::DELTA);

        // Apply the upper bound filter again if provided
        if ($upper !== null) {
            $select->where("{$value} < ?", $data - self::DELTA);
        }

        // Order the results in descending order and set the limit
        $select->order("{$value} DESC")->limit($rightIndex - $offset + 1, $offset - 1);

        // Fetch the results, reverse them, and convert them to an array of floats
        return $this->arrayValuesToFloat(
            Arr::reverse($this->select->getConnection()->fetchCol($select)),
        );
    }

    /**
     * Get the field name used for the value in the select query.
     *
     * This method retrieves the actual field name used for values in the select query,
     * which is part of the SELECT clause.
     *
     * @return string The name of the value field.
     */
    private function getValueFiled()
    {
        // Retrieve the first column in the select query and return its name
        $field = $this->select->getPart(Select::COLUMNS)[0];

        return $field[1];
    }

    /**
     * Convert the values in the array to float.
     *
     * This method converts all values in the provided array to floats.
     * It's used for ensuring that the values returned from the query are in a consistent numeric format.
     *
     * @param array $prices The array of values to convert.
     *
     * @return array The converted array of floats.
     */
    private function arrayValuesToFloat($prices)
    {
        $returnPrices = [];

        // Ensure the provided data is an array and not empty
        if (is_array($prices) && ! empty($prices)) {
            // Map all the values to float
            $returnPrices = collect($prices)->map('floatval');
        }

        return $returnPrices;
    }
}
