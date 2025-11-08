<?php

namespace SgFlores\Cruder\Traits;

use SgFlores\Cruder\Contracts\ReaderConfigurable;

/**
 * Trait providing default configuration methods for Reader Services.
 *
 * This trait implements the ReaderConfigurable interface with sensible defaults
 * that can be overridden by child classes. All methods return default values
 * but can be overridden for custom behavior.
 */
trait ReaderConfigurationTrait
{
    // ========================================================================
    // --- Query Parameter Methods ---
    // ========================================================================

    /**
     * Query parameter key for search term.
     *
     * @example 'search' for ?search=term
     */
    public function getSearchParam(): string
    {
        return 'search';
    }

    /**
     * Query parameter key for sort column.
     *
     * @example 'sort_by' for ?sort_by=name
     */
    public function getSortByParam(): string
    {
        return 'sort_by';
    }

    /**
     * Query parameter key for sort direction.
     *
     * @example 'sort_direction' for ?sort_direction=desc
     */
    public function getSortDirectionParam(): string
    {
        return 'sort_direction';
    }

    /**
     * Query parameter key for page number.
     *
     * @example 'page' for ?page=3
     */
    public function getPageParam(): string
    {
        return 'page';
    }

    /**
     * Query parameter key for items per page.
     *
     * @example 'per_page' for ?per_page=25
     */
    public function getPerPageParam(): string
    {
        return 'per_page';
    }

    /**
     * Query parameter key for result limit (non-paginated collection).
     *
     * @example 'limit' for ?limit=10
     */
    public function getLimitParam(): string
    {
        return 'limit';
    }

    // ========================================================================
    // --- Relation Methods ---
    // ========================================================================

    /**
     * Default relations to eager load for collection queries.
     */
    public function getCollectionRelations(): array
    {
        return [];
    }

    /**
     * Default relations to eager load for single record queries.
     */
    public function getSingleRecordRelations(): array
    {
        return [];
    }

    // ========================================================================
    // --- Direct Column Methods ---
    // ========================================================================

    /**
     * Direct database columns available for exact matching filters.
     */
    public function getDirectFilterableColumns(): array
    {
        return [];
    }

    /**
     * Direct database columns available for fuzzy text search (LIKE queries).
     */
    public function getDirectTextSearchColumns(): array
    {
        return [];
    }

    /**
     * Direct database columns available for result sorting.
     */
    public function getDirectSortableColumns(): array
    {
        return [];
    }

    // ========================================================================
    // --- Related Column Methods ---
    // ========================================================================

    /**
     * Related model columns available for exact matching filters.
     */
    public function getRelatedFilterableColumns(): array
    {
        return [];
    }

    /**
     * Related model columns available for fuzzy text search (LIKE queries).
     */
    public function getRelatedTextSearchColumns(): array
    {
        return [];
    }

    /**
     * Related model columns available for result sorting.
     */
    public function getRelatedSortableColumns(): array
    {
        return [];
    }

    // ========================================================================
    // --- Default Behavior Methods ---
    // ========================================================================

    /**
     * Default column for sorting when no sort parameter is provided.
     */
    public function getDefaultSortColumn(): string
    {
        return 'id';
    }

    /**
     * Default sort direction when no direction parameter is provided.
     *
     * @return string 'asc'|'desc'
     */
    public function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    // ========================================================================
    // --- Caching Methods ---
    // ========================================================================

    /**
     * Whether to enable query result caching for performance optimization.
     */
    public function isQueryCacheEnabled(): bool
    {
        return false;
    }

    /**
     * Cache lifetime in seconds for query results.
     */
    public function getCacheLifetimeSeconds(): int
    {
        return 3600; // 1 hour
    }

    // ========================================================================
    // --- Advanced Features Methods ---
    // ========================================================================

    /**
     * Direct columns to always select.
     */
    public function getSelectColumns(): array
    {
        return [];
    }

    /**
     * Direct columns to exclude from selection.
     */
    public function getExcludeColumns(): array
    {
        return [];
    }

    /**
     * Whether to include soft-deleted records by default.
     */
    public function shouldIncludeSoftDeleted(): bool
    {
        return false;
    }

    /**
     * Whether to only show soft-deleted records.
     */
    public function shouldOnlyShowSoftDeleted(): bool
    {
        return false;
    }

    /**
     * Whether to transform responses using API resources.
     */
    public function shouldEnableApiResources(): bool
    {
        return false;
    }

    /**
     * API resource class for transforming responses.
     */
    public function getApiResourceClass(): ?string
    {
        return null;
    }

    /**
     * Whether to use chunked processing for large datasets.
     */
    public function shouldEnableChunkedProcessing(): bool
    {
        return false;
    }

    /**
     * Chunk size for processing large datasets.
     */
    public function getChunkSize(): int
    {
        return 1000;
    }

    /**
     * Database connection to use for this service.
     */
    public function getDatabaseConnection(): ?string
    {
        return null;
    }

    // ========================================================================
    // --- Search Strategy Methods ---
    // ========================================================================

    /**
     * Query parameter key for multiple strategies.
     *
     * @example 'strategies' for ?strategies=like,fulltext
     */
    public function getStrategiesParam(): string
    {
        return 'strategies';
    }

    // ========================================================================
    // --- Filter Column Mapping Methods ---
    // ========================================================================

    /**
     * Mapping of request parameter keys to internal filterable column names.
     *
     * This allows API consumers to use friendly parameter names (e.g., 'role_names')
     * while internally mapping them to related column filters (e.g., 'assignedRoles.name').
     *
     * Override this method in child classes to define custom mappings.
     *
     * @example
     * return [
     *     'role_names' => 'assignedRoles.name',
     *     'branch_ids' => 'branches.id',
     *     'department_name' => 'department.name',
     * ];
     *
     * @return array Map of request parameter key => internal column name
     */
    public function getFilterColumnMapping(): array
    {
        return [];
    }

    /**
     * Custom filter column keys that represent computed/virtual filters.
     */
    public function getCustomFilterColumns(): array
    {
        return [];
    }
}
