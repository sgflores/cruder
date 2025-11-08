<?php

namespace SgFlores\Cruder\Traits;

use SgFlores\Cruder\Contracts\CrudConfigurable;

/**
 * Trait providing default configuration methods for CRUD Services.
 *
 * This trait implements the CrudConfigurable interface with sensible defaults
 * that can be overridden by child classes. All methods return default values
 * but can be overridden for custom behavior.
 *
 * Note: This trait only provides CRUD-specific methods. The class using this
 * trait should extend BaseReaderService or use ReaderConfigurationTrait to get all reader methods.
 */
trait CrudConfigurationTrait
{
    // ========================================================================
    // --- Auditing Methods ---
    // ========================================================================

    /**
     * Whether to enable automatic audit trail for record changes.
     */
    public function isAuditTrailEnabled(): bool
    {
        return false;
    }

    /**
     * Database column name for tracking record creator.
     */
    public function getCreatorColumn(): string
    {
        return 'created_by';
    }

    /**
     * Database column name for tracking record updater.
     */
    public function getUpdaterColumn(): string
    {
        return 'updated_by';
    }

    /**
     * Database column name for tracking record deleter (soft delete).
     */
    public function getDeleterColumn(): string
    {
        return 'deleted_by';
    }
}
