<?php

namespace SgFlores\Cruder\Traits;

use InvalidArgumentException;
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
     * @return string
     */
    public function getSearchParam(): string
    {
        return 'search';
    }
    
    /**
     * Query parameter key for sort column.
     * 
     * @example 'sort_by' for ?sort_by=name
     * @return string
     */
    public function getSortByParam(): string
    {
        return 'sort_by';
    }
    
    /**
     * Query parameter key for sort direction.
     * 
     * @example 'sort_direction' for ?sort_direction=desc
     * @return string
     */
    public function getSortDirectionParam(): string
    {
        return 'sort_direction';
    }
    
    /**
     * Query parameter key for pagination size.
     * 
     * @example 'page' for ?page=20
     * @return string
     */
    public function getPaginateParam(): string
    {
        return 'page';
    }
    
    /**
     * Query parameter key for result limit.
     * 
     * @example 'limit' for ?limit=10
     * @return string
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
     * 
     * @return array
     */
    public function getCollectionRelations(): array
    {
        return [];
    }
    
    /**
     * Default relations to eager load for single record queries.
     * 
     * @return array
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
     * 
     * @return array
     */
    public function getDirectFilterableColumns(): array
    {
        return [];
    }
    
    /**
     * Direct database columns available for fuzzy text search (LIKE queries).
     * 
     * @return array
     */
    public function getDirectTextSearchColumns(): array
    {
        return [];
    }
    
    /**
     * Direct database columns available for result sorting.
     * 
     * @return array
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
     * 
     * @return array
     */
    public function getRelatedFilterableColumns(): array
    {
        return [];
    }
    
    /**
     * Related model columns available for fuzzy text search (LIKE queries).
     * 
     * @return array
     */
    public function getRelatedTextSearchColumns(): array
    {
        return [];
    }
    
    /**
     * Related model columns available for result sorting.
     * 
     * @return array
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
     * 
     * @return string
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
     * 
     * @return bool
     */
    public function isQueryCacheEnabled(): bool
    {
        return false;
    }
    
    /**
     * Cache lifetime in seconds for query results.
     * 
     * @return int
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
     * 
     * @return array
     */
    public function getSelectColumns(): array
    {
        return [];
    }
    
    /**
     * Direct columns to exclude from selection.
     * 
     * @return array
     */
    public function getExcludeColumns(): array
    {
        return [];
    }
    
    /**
     * Whether to include soft-deleted records by default.
     * 
     * @return bool
     */
    public function shouldIncludeSoftDeleted(): bool
    {
        return false;
    }
    
    /**
     * Whether to only show soft-deleted records.
     * 
     * @return bool
     */
    public function shouldOnlyShowSoftDeleted(): bool
    {
        return false;
    }
    
    /**
     * Whether to transform responses using API resources.
     * 
     * @return bool
     */
    public function shouldEnableApiResources(): bool
    {
        return false;
    }
    
    /**
     * API resource class for transforming responses.
     * 
     * @return string|null
     */
    public function getApiResourceClass(): ?string
    {
        return null;
    }
    
    /**
     * Whether to use chunked processing for large datasets.
     * 
     * @return bool
     */
    public function shouldEnableChunkedProcessing(): bool
    {
        return false;
    }
    
    /**
     * Chunk size for processing large datasets.
     * 
     * @return int
     */
    public function getChunkSize(): int
    {
        return 1000;
    }
    
    
    /**
     * Database connection to use for this service.
     * 
     * @return string|null
     */
    public function getDatabaseConnection(): ?string
    {
        return null;
    }
    
    // ========================================================================
    // --- Search Strategy Methods ---
    // ========================================================================
    
    /**
     * Whether to enforce search strategies (bypass default search implementation).
     * When true, only registered strategies will be used for search.
     * 
     * @return bool
     */
    public function shouldEnforceSearchStrategies(): bool
    {
        return false;
    }
    
    /**
     * Default search strategy to use when enforceSearchStrategies is true.
     * 
     * @return string|null
     */
    public function getDefaultSearchStrategy(): ?string
    {
        return null;
    }
    
    /**
     * Whether to allow multiple search strategies to run simultaneously.
     * 
     * @return bool
     */
    public function shouldAllowMultipleSearchStrategies(): bool
    {
        return false;
    }
    
    
    /**
     * Query parameter key for single search strategy.
     * 
     * @example 'searchStrategy' for ?searchStrategy=like
     * @return string
     */
    public function getSearchStrategyParam(): string
    {
        return 'searchStrategy';
    }
    
    /**
     * Query parameter key for multiple strategies.
     * 
     * @example 'strategies' for ?strategies=like,fulltext
     * @return string
     */
    public function getStrategiesParam(): string
    {
        return 'strategies';
    }
}
