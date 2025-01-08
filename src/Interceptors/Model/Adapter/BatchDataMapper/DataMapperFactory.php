<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interceptors\Model\Adapter\BatchDataMapper;

use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\NoSuchEntityException;
use Maginium\ElasticIndexer\Datasource\Registry;

/**
 * Factory for creating appropriate data mapper instances based on the entity type.
 */
class DataMapperFactory
{
    /**
     * Registry instance to manage data sources.
     */
    private Registry $registry;

    /**
     * Array of available data mappers keyed by entity type.
     */
    private array $dataMappers;

    /**
     * @param Registry $registry The registry for managing data sources.
     * @param array $dataMappers Associative array of data mappers keyed by entity type.
     */
    public function __construct(
        Registry $registry,
        array $dataMappers = [],
    ) {
        $this->registry = $registry;
        $this->dataMappers = $dataMappers;
    }

    /**
     * Create an instance of the data mapper for the specified entity type.
     *
     * @param string $entityType The entity type for which the data mapper is required.
     *
     * @throws NoSuchEntityException If no data mapper is registered for the given entity type.
     * @throws ConfigurationMismatchException If the data mapper does not implement the required interface.
     *
     * @return void
     */
    public function create(string $entityType): void
    {
        collect($this->dataMappers)->each(function($value, $key) use ($entityType): void {
            // Add the data source to the registry.
            $this->registry->addDatasource($entityType, $key, $value);
        });
    }
}
