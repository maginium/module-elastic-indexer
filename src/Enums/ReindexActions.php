<?php

declare(strict_types=1);

namespace Maginium\ElasticIndexer\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Class ReindexActions.
 *
 * Enumeration for different reindexing actions.
 *
 * @method static self ACTION_ROW() Reindex a single row.
 * @method static self ACTION_LIST() Reindex a list of rows.
 * @method static self ACTION_IDS() Reindex specific IDs.
 */
class ReindexActions extends Enum
{
    /**
     * Reindex action: Single row.
     * Used to reindex a single row.
     */
    #[Label('Single Row')]
    #[Description('Reindex action for a single row.')]
    public const ACTION_ROW = 'index_row';

    /**
     * Reindex action: List of rows.
     * Used to reindex a list of rows.
     */
    #[Label('List of Rows')]
    #[Description('Reindex action for a list of rows.')]
    public const ACTION_LIST = 'index_list';

    /**
     * Reindex action: Specific IDs.
     * Used to reindex specific IDs.
     */
    #[Label('Specific IDs')]
    #[Description('Reindex action for specific IDs.')]
    public const ACTION_IDS = 'index_ids';
}
