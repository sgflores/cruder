<?php

namespace SgFlores\Cruder\Contracts;

/**
 * Interface for CRUD Service configuration contracts.
 *
 * This interface defines the contract that all CRUD services must implement,
 * ensuring consistent behavior and type safety across different implementations.
 */
interface CrudConfigurable
{
    // ========================================================================
    // --- Auditing Constants ---
    // ========================================================================

    /**
     * Whether to enable automatic audit trail for record changes.
     */
    public function isAuditTrailEnabled(): bool;

    /**
     * Database column name for tracking record creator.
     */
    public function getCreatorColumn(): string;

    /**
     * Database column name for tracking record updater.
     */
    public function getUpdaterColumn(): string;

    /**
     * Database column name for tracking record deleter (soft delete).
     */
    public function getDeleterColumn(): string;
}
