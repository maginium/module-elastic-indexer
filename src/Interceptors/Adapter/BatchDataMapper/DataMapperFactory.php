<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interceptors\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\DataMapperFactory as BaseDataMapperFactory;
use Magento\Elasticsearch\Model\Adapter\BatchDataMapperInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

/**
 * Data mapper factory used to create the appropriate mapper class based on model type.
 */
class DataMapperFactory extends BaseDataMapperFactory
{
    /**
     * Key used to define the sort order of data mappers.
     *
     * This constant is used when processing data mappers to sort them
     * in ascending order based on their priority.
     *
     * @var string
     */
    public const SORT_ORDER = 'sortOrder';

    /**
     * Key used to specify the class name of the data mapper.
     *
     * This constant is used to identify and instantiate the appropriate
     * data mapper class for a given model type.
     *
     * @var string
     */
    public const DATA_MAPPER_CLASS = 'dataMapperClass';

    /**
     * Array to hold the data mappers and their configurations.
     *
     * @var array
     */
    private $dataMappers;

    /**
     * Constructor for DataMapperFactory class.
     *
     * @param array $dataMappers - Array of data mappers to initialize the factory.
     */
    public function __construct(array $dataMappers = [])
    {
        // Sort the provided dataMappers array by the 'sortOrder' before storing it in the class property.
        $this->dataMappers = $this->sortDataMappers($dataMappers);
    }

    /**
     * Create instances of the appropriate data mapper(s) based on the model type.
     *
     * @param string $modelType - The type of model for which the data mapper is needed.
     *
     * @throws NoSuchEntityException - Throws if no data mapper is found for the given model type.
     * @throws ConfigurationMismatchException - Throws if the data mapper does not implement the expected interface.
     *
     * @return BatchDataMapperInterface[]|BatchDataMapperInterface - The data mapper instance(s) for the specified model type.
     */
    public function create($modelType)
    {
        // Check if the requested model type exists in the dataMappers array.
        if (! isset($this->dataMappers[$modelType])) {
            return [];
        }

        // Retrieve the corresponding data mapper(s) for the requested model type.
        $dataMapper = $this->dataMappers[$modelType];

        // Container for multiple data mappers if applicable
        $modelsDataMappers = [];

        // Check if $dataMapper is an array (could be multiple mappers).
        if (Validator::isArray($dataMapper)) {
            foreach ($dataMapper as $subEntity => $subDataMapper) {
                $dataMapperClass = $subDataMapper[static::DATA_MAPPER_CLASS] ?? $subDataMapper;

                // Skip the iteration if $dataMapperClass is not a string
                if (! Validator::isString($dataMapperClass)) {
                    continue;
                }

                // Instantiate and validate the data mapper class
                $dataMapperEntity = $this->instantiateDataMapper($dataMapperClass);

                // Store the data mapper instance in the container
                $modelsDataMappers[] = $dataMapperEntity;
            }
        } else {
            // If a single mapper is provided, directly instantiate and validate
            $modelsDataMappers[] = $this->instantiateDataMapper($dataMapper);
        }

        // Return a single instance if there's only one mapper; otherwise, return all
        return $modelsDataMappers;
    }

    /**
     * Instantiate and validate a data mapper class.
     *
     * @param string $dataMapperClass - The class name of the data mapper to instantiate.
     *
     * @throws ConfigurationMismatchException - Throws if the data mapper does not implement the expected interface.
     *
     * @return BatchDataMapperInterface - The instantiated and validated data mapper.
     */
    private function instantiateDataMapper(string $dataMapperClass): BatchDataMapperInterface
    {
        // Create the data mapper instance using the container.
        $dataMapperEntity = Container::resolve($dataMapperClass);

        // Validate the instance against the expected interface.
        if (! $dataMapperEntity instanceof BatchDataMapperInterface) {
            throw new ConfigurationMismatchException(
                __(
                    'Data mapper "%1" must implement interface %2',
                    $dataMapperClass,
                    BatchDataMapperInterface::class,
                ),
            );
        }

        return $dataMapperEntity;
    }

    /**
     * Sort the provided data mappers by their 'sortOrder' in ascending order.
     *
     * @param array $dataMappers - The array of data mappers to be sorted.
     *
     * @return array - Sorted data mappers array.
     */
    private function sortDataMappers(array $dataMappers): array
    {
        // Iterate through each data mapper in the array and process it.
        foreach ($dataMappers as $key => &$dataMapper) {
            // Ensure each data mapper has the required structure (class name and sortOrder).
            $dataMapper = $this->processDataMapper($dataMapper);
        }

        // Now, we need to sort both the outer array and any inner arrays by sortOrder
        uasort($dataMappers, function($a, $b) {
            // Check if both $a and $b are not arrays
            if (! is_array($a) && ! is_array($b)) {
                return $a[static::SORT_ORDER] <=> $b[static::SORT_ORDER];
            }

            // Return 0 if either $a or $b is an array (no sorting)
            return 0;
        });

        // Iterate through the data mappers to check if any of them contain nested data mappers.
        foreach ($dataMappers as &$dataMapper) {
            // If a data mapper contains nested mappers, sort them by their sortOrder.
            if (Validator::isArray($dataMapper)) {
                $this->sortNestedDataMappers($dataMapper);
            }
        }

        // Return the sorted data mappers array.
        return $dataMappers;
    }

    /**
     * Process a single data mapper to ensure it has the required structure (class name and sortOrder).
     *
     * @param mixed $dataMapper - The data mapper to process (could be a string or an array).
     *
     * @return array - The processed data mapper with class name and sortOrder.
     */
    private function processDataMapper($dataMapper): array
    {
        // If the data mapper is an array, process each sub-item inside the array.
        if (Validator::isArray($dataMapper)) {
            foreach ($dataMapper as &$subItem) {
                // If a sub-item is a string, assume it is a class name and process it.
                if (Validator::isString($subItem)) {
                    $subItem = $this->createMapperItem($subItem);
                }
            }
        } else {
            // If the data mapper is not an array, handle it as a single class name.
            $dataMapper = $this->createMapperItem($dataMapper);
        }

        // Return the processed data mapper.
        return $dataMapper;
    }

    /**
     * Create a structured item for a data mapper, including class name and sortOrder.
     *
     * @param string $className - The class name of the data mapper.
     *
     * @return array - A structured array containing 'dataMapperClass' and 'sortOrder'.
     */
    private function createMapperItem(string $className): array
    {
        // Attempt to retrieve the 'sortOrder' for the given class. Default to 0 if not found.
        $sortOrder = Reflection::exists($className) ? ($className::$sortOrder ?? 0) : 0;

        // Return the structured array with class name and sortOrder.
        return [
            static::SORT_ORDER => $sortOrder,
            static::DATA_MAPPER_CLASS => $className,
        ];
    }

    /**
     * Sort nested data mappers (if any) by their 'sortOrder' property.
     *
     * @param array $dataMapper - The data mapper array which might contain nested items to sort.
     */
    private function sortNestedDataMappers(array &$dataMapper): void
    {
        // Iterate through each nested data mapper and sort them by their 'sortOrder'.
        foreach ($dataMapper as &$subItem) {
            // If the sub-item is an array and has the 'sortOrder' key, perform sorting.
            if (Validator::isArray($subItem) && isset($subItem[static::SORT_ORDER])) {
                uasort($dataMapper, fn($a, $b) => $a[static::SORT_ORDER] <=> $b[static::SORT_ORDER]);
            }
        }
    }
}
