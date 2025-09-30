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
     * @return string
     */
    public function getSearchParam(): string;
    
    /**
     * Query parameter key for sort column.
     * 
     * @example 'sort_by' for ?sort_by=name
     * @return string
     */
    public function getSortByParam(): string;
    
    /**
     * Query parameter key for sort direction.
     * 
     * @example 'sort_direction' for ?sort_direction=desc
     * @return string
     */
    public function getSortDirectionParam(): string;
    
    /**
     * Query parameter key for pagination size.
     * 
     * @example 'page' for ?page=20
     * @return string
     */
    public function getPaginateParam(): string;
    
    /**
     * Query parameter key for result limit.
     * 
     * @example 'limit' for ?limit=10
     * @return string
     */
    public function getLimitParam(): string;
    
    // ========================================================================
    // --- Relation Constants ---
    // ========================================================================
    
    /**
     * Default relations to eager load for collection queries.
     * 
     * @return array
     */
    public function getCollectionRelations(): array;
    
    /**
     * Default relations to eager load for single record queries.
     * 
     * @return array
     */
    public function getSingleRecordRelations(): array;

    // ========================================================================
    // --- Direct Column Constants ---
    // ========================================================================
    
    /**
     * Direct database columns available for exact matching filters.
     * 
     * @return array
     */
    public function getDirectFilterableColumns(): array;
    
    /**
     * Direct database columns available for fuzzy text search (LIKE queries).
     * 
     * @return array
     */
    public function getDirectTextSearchColumns(): array;
    
    /**
     * Direct database columns available for result sorting.
     * 
     * @return array
     */
    public function getDirectSortableColumns(): array;
    
    // ========================================================================
    // --- Related Column Constants ---
    // ========================================================================
    
    /**
     * Related model columns available for exact matching filters.
     * 
     * @return array
     */
    public function getRelatedFilterableColumns(): array;
    
    /**
     * Related model columns available for fuzzy text search (LIKE queries).
     * 
     * @return array
     */
    public function getRelatedTextSearchColumns(): array;
    
    /**
     * Related model columns available for result sorting.
     * 
     * @return array
     */
    public function getRelatedSortableColumns(): array;

    // ========================================================================
    // --- Default Behavior Constants ---
    // ========================================================================
    
    /**
     * Default column for sorting when no sort parameter is provided.
     * 
     * @return string
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
     * 
     * @return bool
     */
    public function isQueryCacheEnabled(): bool;
    
    /**
     * Cache lifetime in seconds for query results.
     * 
     * @return int
     */
    public function getCacheLifetimeSeconds(): int;
    
    // ========================================================================
    // --- Advanced Features Constants ---
    // ========================================================================
    
    /**
     * Direct columns to always select.
     * 
     * @return array
     */
    public function getSelectColumns(): array;
    
    /**
     * Direct columns to exclude from selection.
     * 
     * @return array
     */
    public function getExcludeColumns(): array;
    
    /**
     * Whether to include soft-deleted records by default.
     * 
     * @return bool
     */
    public function shouldIncludeSoftDeleted(): bool;
    
    /**
     * Whether to only show soft-deleted records.
     * 
     * @return bool
     */
    public function shouldOnlyShowSoftDeleted(): bool;
    
    /**
     * Whether to transform responses using API resources.
     * 
     * @return bool
     */
    public function shouldEnableApiResources(): bool;
    
    /**
     * API resource class for transforming responses.
     * 
     * @return string|null
     */
    public function getApiResourceClass(): ?string;
    
    /**
     * Whether to use chunked processing for large datasets.
     * 
     * @return bool
     */
    public function shouldEnableChunkedProcessing(): bool;
    
    /**
     * Chunk size for processing large datasets.
     * 
     * @return int
     */
    public function getChunkSize(): int;
    
    /**
     * Cache tags for more granular cache invalidation.
     * 
     * @return array
     */
    public function getCacheTags(): array;
    
    /**
     * Database connection to use for this service.
     * 
     * @return string|null
     */
    public function getDatabaseConnection(): ?string;
}
