<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Interfaces\Mapping;

/**
 * Interface FieldInterface.
 *
 * Defines the standard types and date format constants used in Elasticsearch field mappings.
 * These constants are used to specify the data type and formatting rules for fields in Elasticsearch indices.
 */
interface FieldInterface
{
    /**
     * Represents a field for exact values such as IDs or enumerations.
     * Used for keyword-type fields in Elasticsearch.
     */
    public const TYPE_KEYWORD = 'keyword';

    /**
     * Represents a field that can only store true or false values.
     * Used for boolean-type fields in Elasticsearch.
     */
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * Represents a field for floating-point numbers with double precision.
     * Used for double-type fields in Elasticsearch.
     */
    public const TYPE_DOUBLE = 'double';

    /**
     * Represents a field for integer values.
     * Used for integer-type fields in Elasticsearch.
     */
    public const TYPE_INTEGER = 'integer';

    /**
     * Represents a field for smaller integer values.
     * Used for short-type fields in Elasticsearch.
     */
    public const TYPE_SHORT = 'short';

    /**
     * Represents a field for large integer values.
     * Used for long-type fields in Elasticsearch.
     */
    public const TYPE_LONG = 'long';

    /**
     * Represents a field for full-text searchable content.
     * Used for text-type fields in Elasticsearch.
     */
    public const TYPE_TEXT = 'text';

    /**
     * Represents a field for date values.
     * Used for date-type fields in Elasticsearch.
     */
    public const TYPE_DATE = 'date';

    /**
     * Represents the standard date format for date fields in Elasticsearch.
     * Includes multiple formats:
     * - `yyyy-MM-dd HH:mm:ss` for precise timestamps.
     * - `yyyy-MM-dd` for dates without times.
     * - `epoch_millis` for milliseconds since the Unix epoch.
     */
    public const DATE_FORMAT = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis';

    /**
     * Represents a field for binary data such as encoded files.
     * Used for binary-type fields in Elasticsearch.
     */
    public const TYPE_BINARY = 'binary';

    /**
     * Represents a field for storing JSON objects or nested structures.
     * Used for object-type fields in Elasticsearch.
     */
    public const TYPE_OBJECT = 'object';
}
