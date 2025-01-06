<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interceptors\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\DataMapperResolver as BaseDataMapperResolver;
use Magento\Elasticsearch\Model\Adapter\BatchDataMapperInterface;
use Magento\Elasticsearch\Model\Config;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\NoSuchEntityException;
use Maginium\Framework\Support\Facades\Concurrency;
use Maginium\Framework\Support\Php;
use Validator;

/**
 * Data mapper factory used to create the appropriate mapper class based on model type.
 */
class DataMapperResolver extends BaseDataMapperResolver
{
    /**
     * An array holding the data mappers for various model types.
     *
     * @var array
     */
    private $modelsDataMappers = [];

    /**
     * The factory responsible for creating instances of data mappers.
     *
     * @var DataMapperFactory
     */
    private $dataMapperFactory;

    /**
     * Constructor.
     *
     * Initializes the object and loads environment configurations.
     *
     * @param DataMapperFactory $dataMapperFactory The factory used to create data mappers for models.
     */
    public function __construct(DataMapperFactory $dataMapperFactory)
    {
        // Store the data mapper factory instance for later use.
        $this->dataMapperFactory = $dataMapperFactory;
    }

    /**
     * Map the provided document data using all data mappers for the model type.
     *
     * @param array $documentData The data to be mapped.
     * @param int|string $storeId The store ID.
     * @param array $context Additional context for the mapping.
     *
     * @return array The mapped data combined from all mappers.
     */
    public function map(array $documentData, $storeId, array $context = [])
    {
        $modelType = $context['entityType'] ?? Config::ELASTICSEARCH_TYPE_DEFAULT;

        // Retrieve all applicable data mappers for the given model type.
        $mappers = $this->getDataMappers($modelType);

        if (Validator::isEmpty($mappers)) {
            return $documentData;
        }

        // Prepare tasks for concurrency
        $tasks = collect($mappers)->map(fn($mapper) => fn() => $mapper->map($documentData, $storeId, $context))->toArray();

        // Execute all tasks concurrently and collect results
        $results = Concurrency::run($tasks);

        // Merge all results using array_merge_recursive
        return collect($results)
            ->reduce(fn($carry, $item) => Php::deepMerge($carry, $item), []);
    }

    /**
     * Retrieve instances of data mapper(s) for the specified model type.
     *
     * @param string $modelType The type of the model.
     *
     * @throws NoSuchEntityException If no data mapper exists for the model type.
     * @throws ConfigurationMismatchException If a mapper does not implement the required interface.
     *
     * @return BatchDataMapperInterface[] An array of data mapper instances.
     */
    private function getDataMappers(string $modelType): array
    {
        // Return cached mappers if they exist for the model type.
        if (! isset($this->modelsDataMappers[$modelType])) {
            // Retrieve mappers from the factory, which may return a single mapper or an array.
            $mappers = $this->dataMapperFactory->create($modelType);

            // Ensure the result is always an array of mappers.
            $this->modelsDataMappers[$modelType] = is_array($mappers) ? $mappers : [$mappers];

            // Validate that all mappers implement the required interface.
            foreach ($this->modelsDataMappers[$modelType] as $mapper) {
                if (! $mapper instanceof BatchDataMapperInterface) {
                    throw new ConfigurationMismatchException(
                        __(
                            'Data mapper "%1" must implement interface %2',
                            get_class($mapper),
                            BatchDataMapperInterface::class,
                        ),
                    );
                }
            }
        }

        return $this->modelsDataMappers[$modelType];
    }
}
