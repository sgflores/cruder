<?php

namespace SgFlores\Cruder\Contracts;

/**
 * Interface for Reader Service configuration contracts.
 *
 * This interface defines the contract that all reader services must implement,
 * ensuring consistent behavior and type safety across different implementations.
 */
interface ReaderConfigurable
{
    // ========================================================================
    // --- Query Parameter Constants ---
    // ========================================================================

    /**
     * Query parameter key for search term.
     *
     * @example 'search' for ?search=term
     */
    public function getSearchParam(): string;

    /**
     * Query parameter key for sort column.
     *
     * @example 'sort_by' for ?sort_by=name
     */
    public function getSortByParam(): string;

    /**
     * Query parameter key for sort direction.
     *
     * @example 'sort_direction' for ?sort_direction=desc
     */
    public function getSortDirectionParam(): string;

    /**
     * Query parameter key for page number.
     *
     * @example 'page' for ?page=3
     */
    public function getPageParam(): string;

    /**
     * Query parameter key for items per page.
     *
     * @example 'per_page' for ?per_page=25
     */
    public function getPerPageParam(): string;

    /**
     * Query parameter key for result limit (non-paginated collection).
     *
     * @example 'limit' for ?limit=10
     */
    public function getLimitParam(): string;

    // ========================================================================
    // --- Relation Constants ---
    // ========================================================================

    /**
     * Default relations to eager load for collection queries.
     */
    public function getCollectionRelations(): array;

    /**
     * Default relations to eager load for single record queries.
     */
    public function getSingleRecordRelations(): array;

    // ========================================================================
    // --- Direct Column Constants ---
    // ========================================================================

    /**
     * Direct database columns available for exact matching filters.
     */
    public function getDirectFilterableColumns(): array;

    /**
     * Direct database columns available for fuzzy text search (LIKE queries).
     */
    public function getDirectTextSearchColumns(): array;

    /**
     * Direct database columns available for result sorting.
     */
    public function getDirectSortableColumns(): array;

    // ========================================================================
    // --- Related Column Constants ---
    // ========================================================================

    /**
     * Related model columns available for exact matching filters.
     */
    public function getRelatedFilterableColumns(): array;

    /**
     * Related model columns available for fuzzy text search (LIKE queries).
     */
    public function getRelatedTextSearchColumns(): array;

    /**
     * Related model columns available for result sorting.
     */
    public function getRelatedSortableColumns(): array;

    // ========================================================================
    // --- Default Behavior Constants ---
    // ========================================================================

    /**
     * Default column for sorting when no sort parameter is provided.
     */
    public function getDefaultSortColumn(): string;

    /**
     * Default sort direction when no direction parameter is provided.
     *
     * @return string 'asc'|'desc'
     */
    public function getDefaultSortDirection(): string;

    // ========================================================================
    // --- Caching Constants ---
    // ========================================================================

    /**
     * Whether to enable query result caching for performance optimization.
     */
    public function isQueryCacheEnabled(): bool;

    /**
     * Cache lifetime in seconds for query results.
     */
    public function getCacheLifetimeSeconds(): int;

    // ========================================================================
    // --- Advanced Features Constants ---
    // ========================================================================

    /**
     * Direct columns to always select.
     */
    public function getSelectColumns(): array;

    /**
     * Direct columns to exclude from selection.
     */
    public function getExcludeColumns(): array;

    /**
     * Whether to include soft-deleted records by default.
     */
    public function shouldIncludeSoftDeleted(): bool;

    /**
     * Whether to only show soft-deleted records.
     */
    public function shouldOnlyShowSoftDeleted(): bool;

    /**
     * Whether to transform responses using API resources.
     */
    public function shouldEnableApiResources(): bool;

    /**
     * API resource class for transforming responses.
     */
    public function getApiResourceClass(): ?string;

    /**
     * Whether to use chunked processing for large datasets.
     */
    public function shouldEnableChunkedProcessing(): bool;

    /**
     * Chunk size for processing large datasets.
     */
    public function getChunkSize(): int;

    /**
     * Database connection to use for this service.
     */
    public function getDatabaseConnection(): ?string;

    // ========================================================================
    // --- Filter Column Mapping ---
    // ========================================================================

    /**
     * Mapping of request parameter keys to internal filterable column names.
     *
     * This allows API consumers to use friendly parameter names (e.g., 'role_names')
     * while internally mapping them to related column filters (e.g., 'assignedRoles.name').
     *
     * @example
     * return [
     *     'role_names' => 'assignedRoles.name',
     *     'branch_ids' => 'branches.id',
     * ];
     *
     * @return array Map of request parameter key => internal column name
     */
    public function getFilterColumnMapping(): array;

    /**
     * Custom filter columns that do not exist directly in the database schema.
     *
     * These keys can be used to implement computed/virtual filters. Columns declared
     * here bypass the automatic column validation checks and are expected to be handled
     * manually in the service (e.g. inside applyCustomFilterColumn).
     */
    public function getCustomFilterColumns(): array;
}
