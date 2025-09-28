<?php

namespace SgFlores\Cruder;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\HookService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Strategies\Search\FullTextSearchStrategy;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Strategies\Hooks\CallableHook;

/**
 * Base CRUD Service with SOLID principles implementation.
 * 
 * This abstract class provides a foundation for CRUD operations following SOLID principles:
 * - Single Responsibility: Each service handles one concern
 * - Open/Closed: Extensible through strategies and hooks
 * - Liskov Substitution: Child classes can be substituted
 * - Interface Segregation: Clear contracts for each service
 * - Dependency Inversion: Depends on abstractions, not concretions
 * 
 * The service uses the Strategy pattern for search and export functionality,
 * and the Observer pattern for hooks, making it highly extensible and maintainable.
 * 
 * COLUMN VALIDATION:
 * The service includes built-in column validation to ensure security and prevent
 * unauthorized access to sensitive data. Only columns declared in the following
 * constants can be used for filtering, sorting, or searching:
 * - DIRECT_FILTERABLE_COLUMNS: Direct database columns for exact filtering
 * - RELATED_FILTERABLE_COLUMNS: Related model columns for exact filtering
 * - DIRECT_SORTABLE_COLUMNS: Direct database columns for sorting
 * - RELATED_SORTABLE_COLUMNS: Related model columns for sorting
 * - DIRECT_TEXT_SEARCH_COLUMNS: Direct database columns for text search
 * - RELATED_TEXT_SEARCH_COLUMNS: Related model columns for text search
 * 
 * Attempting to use undeclared columns will throw an InvalidArgumentException
 * with a helpful error message listing the allowed columns.
 */
abstract class BaseCrudService
{
    /**
     * The Eloquent model instance this service operates on.
     * 
     * @var Model
     */
    protected $model;

    /**
     * Search service for managing search strategies.
     * 
     * @var SearchService
     */
    protected $searchService;

    /**
     * Export service for managing export strategies.
     * 
     * @var ExportService
     */
    protected $exportService;

    /**
     * Hook service for managing query hooks.
     * 
     * @var HookService
     */
    protected $hookService;

    /**
     * Query logger for logging database queries.
     * 
     * @var QueryLogger
     */
    protected $queryLogger;

    
    /**
     * Query parameter key for search term.
     * 
     * @example 'search' for ?search=term
     * @var string
     */
    protected const SEARCH_PARAM = 'search';
    
    /**
     * Query parameter key for sort column.
     * 
     * @example 'sort_by' for ?sort_by=name
     * @var string
     */
    protected const SORT_BY_PARAM = 'sort_by';
    
    /**
     * Query parameter key for sort direction.
     * 
     * @example 'sort_direction' for ?sort_direction=desc
     * @var string
     */
    protected const SORT_DIRECTION_PARAM = 'sort_direction';
    
    /**
     * Query parameter key for pagination size.
     * 
     * @example 'page' for ?page=20
     * @var string
     */
    protected const PAGINATE_PARAM = 'page';
    
    /**
     * Query parameter key for result limit.
     * 
     * @example 'limit' for ?limit=10
     * @var string
     */
    protected const LIMIT_PARAM = 'limit';
    
    /**
     * Default relations to eager load for collection queries.
     * 
     * @var array
     */
    protected const COLLECTION_RELATIONS = [];
    
    /**
     * Default relations to eager load for single record queries.
     * 
     * @var array
     */
    protected const SINGLE_RECORD_RELATIONS = [];

    /**
     * Whether to enable automatic audit trail for record changes.
     * 
     * @var bool
     */
    protected const AUDIT_TRAIL_ENABLED = false;
    
    /**
     * Database column name for tracking record creator.
     * 
     * @var string
     */
    protected const CREATOR_COLUMN = 'created_by';
    
    /**
     * Database column name for tracking record updater.
     * 
     * @var string
     */
    protected const UPDATER_COLUMN = 'updated_by';
    
    /**
     * Database column name for tracking record deleter (soft delete).
     * 
     * @var string
     */
    protected const DELETER_COLUMN = 'deleted_by';

    /**
     * Magic column name for counting records.
     * 
     * @var string
     */
    protected const MAGIC_COUNT = '_is_count_';
    
    // ========================================================================
    // --- Direct Column Constants ---
    // ========================================================================
    
    /**
     * Direct database columns available for exact matching filters.
     * 
     * @var array
     */
    protected const DIRECT_FILTERABLE_COLUMNS = [];
    
    /**
     * Direct database columns available for fuzzy text search (LIKE queries).
     * 
     * @var array
     */
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [];
    
    /**
     * Direct database columns available for result sorting.
     * 
     * @var array
     */
    protected const DIRECT_SORTABLE_COLUMNS = [];
    
    // ========================================================================
    // --- Related Column Constants ---
    // ========================================================================
    
    /**
     * Related model columns available for exact matching filters.
     * 
     * @var array
     */
    protected const RELATED_FILTERABLE_COLUMNS = [];
    
    /**
     * Related model columns available for fuzzy text search (LIKE queries).
     * 
     * @var array
     */
    protected const RELATED_TEXT_SEARCH_COLUMNS = [];
    
    /**
     * Related model columns available for result sorting.
     * 
     * @var array
     */
    protected const RELATED_SORTABLE_COLUMNS = [];
    
    
    /**
     * Default column for sorting when no sort parameter is provided.
     * 
     * @var string
     */
    protected const DEFAULT_SORT_COLUMN = 'id';
    
    /**
     * Default sort direction when no direction parameter is provided.
     * 
     * @var string 'asc'|'desc'
     */
    protected const DEFAULT_SORT_DIRECTION = 'asc';
    
    /**
     * Whether to enable query result caching for performance optimization.
     * 
     * @var bool
     */
    protected const QUERY_CACHE_ENABLED = false;
    
    /**
     * Cache lifetime in seconds for query results.
     * 
     * How long cached query results should be stored before expiring.
     * 
     * @var int
     */
    protected const CACHE_LIFETIME_SECONDS = 3600; // 1 hour
    
    // ========================================================================
    // --- Advanced Features Constants ---
    // ========================================================================
    
    /**
     * Direct columns to always select.
     * 
     * @var array
     */
    protected const SELECT_COLUMNS = [];
    
    /**
     * Direct columns to exclude from selection.
     * 
     * @var array
     */
    protected const EXCLUDE_COLUMNS = [];
    
    /**
     * Whether to include soft-deleted records by default.
     * 
     * @var bool
     */
    protected const INCLUDE_SOFT_DELETED = false;
    
    /**
     * Whether to only show soft-deleted records.
     * 
     * @var bool
     */
    protected const ONLY_SOFT_DELETED = false;
    
    /**
     * Whether to transform responses using API resources.
     * 
     * @var bool
     */
    protected const ENABLE_API_RESOURCES = false;
    
    /**
     * API resource class for transforming responses.
     * 
     * @var string|null
     */
    protected const API_RESOURCE_CLASS = null;
    
    /**
     * Validation rules for create operations.
     * 
     * @var array
     */
    protected const CREATE_VALIDATION_RULES = [];
    
    /**
     * Validation rules for update operations.
     * 
     * @var array
     */
    protected const UPDATE_VALIDATION_RULES = [];
    
    /**
     * Whether to use chunked processing for large datasets.
     * 
     * @var bool
     */
    protected const ENABLE_CHUNKED_PROCESSING = false;
    
    /**
     * Chunk size for processing large datasets.
     * 
     * @var int
     */
    protected const CHUNK_SIZE = 1000;
    
    /**
     * Cache tags for more granular cache invalidation.
     * 
     * @var array
     */
    protected const CACHE_TAGS = [];
    
    /**
     * Database connection to use for this service.
     * 
     * @var string|null
     */
    protected const DATABASE_CONNECTION = null;
    
    // ========================================================================
    // --- Advanced Features Constants ---
    // ========================================================================
    
    /**
     * Whether to enable full-text search.
     * 
     * @var bool
     */
    protected const ENABLE_FULLTEXT_SEARCH = false;
    
    /**
     * Full-text search columns.
     * 
     * @var array
     */
    protected const FULLTEXT_SEARCH_COLUMNS = [];
    
    /**
     * Query hooks for before/after query execution.
     * 
     * @var array
     */
    protected const QUERY_HOOKS = [
        'before_find' => [],
        'after_find' => [],
        'before_create' => [],
        'after_create' => [],
        'before_update' => [],
        'after_update' => [],
        'before_delete' => [],
        'after_delete' => []
    ];

    /**
     * BaseCrudService constructor.
     * 
     * Initializes the service with the model and sets up the service dependencies.
     * Child classes can override configureServices() to customize behavior.
     * 
     * @param Model $model The Eloquent model instance to work with
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->initializeServices();
    }

    /**
     * Initializes the service dependencies.
     * 
     * Sets up the search, export, and hook services with default strategies.
     * Child classes can override configureServices() to add custom strategies.
     * 
     * @return void
     */
    protected function initializeServices(): void
    {
        $this->searchService = new SearchService();
        $this->exportService = new ExportService();
        $this->hookService = new HookService();
        $this->queryLogger = new QueryLogger();
        
        $this->configureDefaultStrategies();
        $this->configureServices();
    }

    /**
     * Configures default strategies for search and export.
     * 
     * @return void
     */
    protected function configureDefaultStrategies(): void
    {
        // Add default search strategies
        $this->searchService->addStrategy('fulltext', new FullTextSearchStrategy());
        $this->searchService->addStrategy('like', new LikeSearchStrategy());
        
        // Add default export strategies
        $this->exportService->addStrategy('csv', new CsvExportStrategy());
        $this->exportService->addStrategy('json', new JsonExportStrategy());
    }

    /**
     * Configures custom services and strategies.
     * 
     * Override this method in child classes to add custom strategies,
     * hooks, or modify service behavior.
     * 
     * @return void
     */
    protected function configureServices(): void
    {
        // Base implementation does nothing.
        // Override in child classes to add custom configuration.
    }

    // ========================================================================
    // --- Public CRUD and Count Methods ---
    // ========================================================================

    /**
     * Retrieves a collection of records with advanced filtering, sorting, and pagination.
     * 
     * @param array $filters Query options including paginate, limit, search, sort_by, sort_direction, and additional filter options
     * @param mixed $withRelations Relations to eager load (boolean, array, string, or null)
     * @return Collection|LengthAwarePaginator Collection of models or paginated results
     */
    public function findAll(array $filters = [], $withRelations = null): Collection|LengthAwarePaginator
    {
        // Execute before_find hooks
        $filters = $this->hookService->executeHooks('before_find', $filters);
        
        $isPaginated = isset($filters[static::PAGINATE_PARAM]) || isset($filters[static::LIMIT_PARAM]);

        $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::COLLECTION_RELATIONS);
        $query = $this->createQueryBuilder()->with($relationsToLoad);
        $query = $this->applyQueryFilters($query, $filters);
        
        // child classes can override this method to add additional query constraints
        $this->applyCustomQueryConstraints($query, $filters);

        $startTime = microtime(true);

        if (static::QUERY_CACHE_ENABLED && !$isPaginated) {
            $cacheKey = $this->buildCacheKey($filters, 'all');
            $result = Cache::remember($cacheKey, static::CACHE_LIFETIME_SECONDS, fn() => $query->get());
        } else {
        if (isset($filters[static::PAGINATE_PARAM])) {
                $result = $query->paginate($filters[static::PAGINATE_PARAM]);
            } elseif (isset($filters[static::LIMIT_PARAM])) {
                $result = $query->limit($filters[static::LIMIT_PARAM])->get();
            } else {
                $result = $query->get();
            }
        }

        // Log query for debugging
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $this->queryLogger->logQuery('find', $query, $executionTime, [
            'filters' => $filters,
            'is_paginated' => $isPaginated,
            'result_count' => $result instanceof \Illuminate\Support\Collection ? $result->count() : 'paginated'
        ]);

        $result = $this->transformResponse($result, $filters);
        
        // Execute after_find hooks
        return $this->hookService->executeHooks('after_find', $result);
    }

    /**
     * Retrieves a single record by its primary key with optional eager loading.
     * 
     * @param int|string $id The primary key value to search for
     * @param mixed $withRelations Relations to eager load (boolean, array, string, or null)
     * @return Model|null The found model or null if not found
     */
    public function findById(int|string $id, $withRelations = null): ?Model
    {
        $primaryKey = $this->model->getKeyName();
        $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::SINGLE_RECORD_RELATIONS);
        
        $result = $this->model->newQuery()
                           ->with($relationsToLoad)
                           ->where($primaryKey, $id)
                           ->first();
        
        return $result ? $this->transformResponse($result) : null;
    }
    
    /**
     * Counts records matching the given filter criteria.
     * 
     * @param array $filters Query options for filtering (same as findAll() method)
     * @param bool $includeSoftDeleted Whether to include soft deleted records
     * @return int Number of matching records
     */
    public function count(array $filters = [], bool $includeSoftDeleted = false): int
    {
        if (static::QUERY_CACHE_ENABLED) {
            $cacheKey = $this->buildCacheKey($filters, 'count');
            
            return Cache::remember($cacheKey, static::CACHE_LIFETIME_SECONDS, function () use ($filters, $includeSoftDeleted) {
                $filters[static::MAGIC_COUNT] = true; 
                $query = $this->createQueryBuilder($includeSoftDeleted);
                $query = $this->applyQueryFilters($query, $filters);
                return $query->toBase()->count();
            });
        }
        
        $filters[static::MAGIC_COUNT] = true; 
        $query = $this->createQueryBuilder($includeSoftDeleted);
        $query = $this->applyQueryFilters($query, $filters);
        
        $startTime = microtime(true);
        $count = $query->toBase()->count();
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // Log query for debugging
        $this->queryLogger->logQuery('count', $query, $executionTime, [
            'filters' => $filters,
            'include_soft_deleted' => $includeSoftDeleted,
            'count' => $count
        ]);
        
        return $count;
    }
    
    /**
     * Creates a new record with optional eager loading of relations.
     * 
     * @param array $data The data to create the record with
     * @param mixed $withRelations Relations to eager load on the returned model (boolean, array, string, or null)
     * @return Model The created model
     * @throws \Exception If creation fails
     */
    public function create(array $data, $withRelations = null): Model
    {
        // Execute before_create hooks
        $data = $this->hookService->executeHooks('before_create', $data);
        
        // Transaction for atomic creation
        return DB::transaction(function () use ($data, $withRelations) {
            $startTime = microtime(true);
            
            // Validate data if validation rules are defined
            $validatedData = $this->validateData($data, 'create');
            $processedData = $this->prepareCreateData($validatedData);
            $createdModel = $this->model->create($processedData);
            $this->clearCache();
            
            // Eager load relations if requested
            $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::SINGLE_RECORD_RELATIONS);
            if (!empty($relationsToLoad)) {
                $primaryKey = $this->model->getKeyName();
                $createdModel = $this->model->newQuery()
                                          ->with($relationsToLoad)
                                          ->where($primaryKey, $createdModel->{$primaryKey})
                                          ->first();
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log query for debugging
            $this->queryLogger->logQuery('create', $this->model->newQuery(), $executionTime, [
                'data' => $processedData,
                'created_id' => $createdModel->getKey(),
                'with_relations' => $relationsToLoad
            ]);
            
            $result = $this->transformResponse($createdModel);
            
            // Execute after_create hooks
            return $this->hookService->executeHooks('after_create', $result);
        });
    }

    /**
     * Updates an existing record by its primary key with optional eager loading.
     * 
     * @param int|string $id The primary key value of the record to update
     * @param array $data The data to update the record with
     * @param mixed $withRelations Relations to eager load on the returned model (boolean, array, string, or null)
     * @return Model|null The updated model or null if not found
     * @throws \Exception If update fails
     */
    public function update(int|string $id, array $data, $withRelations = null): ?Model
    {
        $primaryKey = $this->model->getKeyName();
        // Find without relations to speed up the transaction
        $existingModel = $this->model->newQuery()->where($primaryKey, $id)->first(); 
        if (!$existingModel) { return null; }

        // Execute before_update hooks
        $data = $this->hookService->executeHooks('before_update', $data);

        return DB::transaction(function () use ($existingModel, $data, $id, $primaryKey, $withRelations) {
            $startTime = microtime(true);
            
            // Validate data if validation rules are defined
            $validatedData = $this->validateData($data, 'update');
            $processedData = $this->prepareUpdateData($validatedData);
            $existingModel->update($processedData);
            
            $this->clearCache();
            
            // Re-load with relations using dynamic PK
            $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::SINGLE_RECORD_RELATIONS);
            $updatedModel = $this->model->newQuery()
                               ->with($relationsToLoad)
                               ->where($primaryKey, $id)
                               ->first();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log query for debugging
            $this->queryLogger->logQuery('update', $this->model->newQuery(), $executionTime, [
                'id' => $id,
                'data' => $processedData,
                'with_relations' => $relationsToLoad
            ]);
            
            $result = $this->transformResponse($updatedModel);
            
            // Execute after_update hooks
            return $this->hookService->executeHooks('after_update', $result);
        });
    }

    /**
     * Deletes a record by its primary key with optional force deletion.
     * 
     * @param int|string $id The primary key value of the record to delete
     * @param bool $force Whether to force delete (bypass soft delete)
     * @return bool True if the record was deleted successfully
     * @throws \Exception If deletion fails
     */
    public function delete(int|string $id, bool $force = false): bool
    {
        $primaryKey = $this->model->getKeyName();
        // Find without relations to speed up the transaction
        $modelToDelete = $this->model->newQuery()->where($primaryKey, $id)->first(); 
        if (!$modelToDelete) { return false; }
        
        // Execute before_delete hooks
        $modelToDelete = $this->hookService->executeHooks('before_delete', $modelToDelete);
        
        return DB::transaction(function () use ($modelToDelete, $force) {
            $startTime = microtime(true);
            
            if (!$force && method_exists($modelToDelete, 'bootSoftDeletes')) {
                $modelToDelete = $this->prepareDeleteData($modelToDelete);
            }
            $deletionSuccessful = $force ? (bool) $modelToDelete->forceDelete() : (bool) $modelToDelete->delete();
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log query for debugging
            $this->queryLogger->logQuery('delete', $this->model->newQuery(), $executionTime, [
                'id' => $modelToDelete->getKey(),
                'force' => $force,
                'successful' => $deletionSuccessful
            ]);
            
            if ($deletionSuccessful) {
                $this->clearCache();
                // Execute after_delete hooks
                $this->hookService->executeHooks('after_delete', $modelToDelete);
            }
            return $deletionSuccessful;
        });
    }

    // ========================================================================
    // --- Mass Actions ---
    // ========================================================================

    /**
     * Creates multiple records in a single optimized database operation.
     * 
     * @param array $data Array of data arrays for bulk creation
     * @return bool True if all records were created successfully
     * @throws \Exception If bulk creation fails
     */
    public function bulkCreate(array $data): bool
    {
        // Add auditing fields if enabled
        if (static::AUDIT_TRAIL_ENABLED && Auth::check() && $this->model->isFillable(static::CREATOR_COLUMN)) {
            $currentUserId = Auth::id();
            $data = array_map(function ($recordItem) use ($currentUserId) {
                $recordItem[static::CREATOR_COLUMN] = $currentUserId;
                $recordItem['created_at'] = now();
                $recordItem['updated_at'] = now();
                return $recordItem;
            }, $data);
        }

        $success = $this->model->insert($data);
        if ($success) $this->clearCache();
        return $success;
    }

    /**
     * Updates multiple records matching the given filter criteria.
     * 
     * Check the count of records to update before performing the operation.
     * If the count is 0, do not perform the operation.
     * @param array $filters Filter options to determine which records to update
     * @param array $data The data to update all matching records with
     * @return int Number of records that were updated
     * @throws \Exception If bulk update fails
     */
    public function bulkUpdate(array $filters, array $data): int
    {
        $count = $this->count($filters);
        if ($count === 0) {
            return 0;
        }

        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
        $processedData = $this->prepareUpdateData($data); // Adds updated_by
        
        $updatedCount = $query->update($processedData);
        if ($updatedCount > 0) $this->clearCache();
        return $updatedCount;
    }

    /**
     * Deletes multiple records matching the given filter criteria.
     * 
     * Check the count of records to delete before performing the operation.
     * If the count is 0, do not perform the operation.
     * @param array $filters Filter options to determine which records to delete
     * @param bool $force Whether to force delete (bypass soft delete)
     * @return int Number of records that were deleted
     * @throws \Exception If bulk deletion fails
     */
    public function bulkDelete(array $filters, bool $force = false): int
    {
        $count = $this->count($filters);
        if ($count === 0) {
            return 0;
        }

        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
        return DB::transaction(function () use ($query, $force) {
            if ($force) {
                $deletedCount = $query->forceDelete();
            } else {
                // Apply deleted_by logic before soft deleting
                if (static::AUDIT_TRAIL_ENABLED && Auth::check() && in_array(SoftDeletes::class, class_uses_recursive($this->model)) && $this->model->isFillable(static::DELETER_COLUMN)) {
                    $query->update([
                        static::DELETER_COLUMN => Auth::id(),
                    ]);
                }
                $deletedCount = $query->delete();
            }
            if ($deletedCount > 0) $this->clearCache();
            return $deletedCount;
        });
    }

    // ========================================================================
    // --- Query Building and Hooks ---
    // ========================================================================

    /**
     * Creates a new query builder instance for the model.
     * 
     * @param bool $includeSoftDeleted Whether to include soft-deleted records
     * @return Builder The Eloquent query builder instance
     */
    protected function createQueryBuilder(bool $includeSoftDeleted = false): Builder
    {
        $queryBuilder = $this->model->newQuery();
        
        if (static::DATABASE_CONNECTION) {
            $queryBuilder->on(static::DATABASE_CONNECTION);
        }
        
        if ($includeSoftDeleted && in_array(SoftDeletes::class, class_uses_recursive($this->model))) {
            $queryBuilder->withTrashed();
        }
        
        return $queryBuilder;
    }
    
    /**
     * Generates a cache key for the given query options and operation type.
     * 
     * @param array $filters Query options to include in the cache key
     * @param string $operation Type of query operation (e.g., 'all', 'count')
     * @return string The generated cache key
     */
    protected function buildCacheKey(array $filters, string $operation = 'all'): string
    {
        return sprintf(
            '%s:%s:%s:%s',
            $this->model->getTable(),
            $operation,
            md5(json_encode($filters)),
            Auth::id() ?? 'guest'
        );
    }
    
    /**
     * Determines which relations to eager load based on the provided parameter.
     * 
     * @param mixed $withRelations The relations to load (boolean, array, string, or null)
     * @param array $defaultRelations Default relations to use if withRelations is null or true
     * @return array Array of relation names to eager load
     */
    protected function resolveRelationsToLoad($withRelations = null, array $defaultRelations = []): array
    {
        // If explicitly set to false, return empty array
        if ($withRelations === false) {
            return [];
        }
        
        // If specific relations are provided (array or string), use them
        if (is_array($withRelations) && !empty($withRelations)) {
            return $withRelations;
        }
        
        if (is_string($withRelations) && !empty($withRelations)) {
            return array_map('trim', explode(',', $withRelations));
        }
        
        // If true or null, return default relations
        return $defaultRelations;
    }
    
    /**
     * Applies all filtering and sorting to the query.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options to apply
     * @return Builder The modified query builder
     */
    protected function applyQueryFilters(Builder $query, array $filters): Builder
    {
        $searchTerm = $filters[static::SEARCH_PARAM] ?? null;

        // 1. Apply soft delete constraints
        $this->applySoftDeleteConstraints($query, $filters);

        // 2. Apply field selection
        $this->applyFieldSelection($query, $filters);

        // 3. Apply text search if search term provided
        if (!empty($searchTerm)) {
            $this->applyTextSearch($query, $searchTerm);
        }

        // 4. Apply column filters
        $this->applyColumnFilters($query, $filters);

        // 5. Apply advanced filters
        $this->applyAdvancedFilters($query, $filters);

        // 6. Apply sorting (skip for count queries)
        if (!array_key_exists(static::MAGIC_COUNT, $filters) || $filters[static::MAGIC_COUNT] === false) {
            $this->applySorting($query, $filters);
        }

        return $query;
    }

    /**
     * Applies fuzzy text search using LIKE operator on configured columns.
     * 
     * @param Builder $queryBuilder The Eloquent query builder instance
     * @param string $searchTerm The search term to look for
     * @return void
     * @throws \InvalidArgumentException If no searchable columns are declared
     */
    protected function applyTextSearch(Builder $queryBuilder, string $searchTerm): void
    {
        // Validate that searchable columns are declared
        $searchableColumns = array_merge(
            static::DIRECT_TEXT_SEARCH_COLUMNS,
            static::RELATED_TEXT_SEARCH_COLUMNS
        );
        
        if (empty($searchableColumns)) {
            throw new \InvalidArgumentException(
                "No searchable columns are declared. " .
                "Please define DIRECT_TEXT_SEARCH_COLUMNS or RELATED_TEXT_SEARCH_COLUMNS to enable text search."
            );
        }
        
        // Determine search strategy based on configuration
        $searchType = static::ENABLE_FULLTEXT_SEARCH && !empty(static::FULLTEXT_SEARCH_COLUMNS) ? 'fulltext' : 'like';
        
        // Prepare search configuration
        $config = $this->getSearchConfig($searchType);
        
        // Use search service to apply the appropriate strategy
        $this->searchService->search($queryBuilder, $searchTerm, $config);
    }

    /**
     * Gets search configuration for the specified search type.
     * 
     * @param string $searchType The search type ('fulltext' or 'like')
     * @return array Search configuration
     */
    protected function getSearchConfig(string $searchType): array
    {
        if ($searchType === 'fulltext') {
            return [
                'type' => 'fulltext',
                'enabled' => static::ENABLE_FULLTEXT_SEARCH,
                'columns' => static::FULLTEXT_SEARCH_COLUMNS
            ];
        }
        
        return [
            'type' => 'like',
            'enabled' => true,
            'direct_columns' => static::DIRECT_TEXT_SEARCH_COLUMNS,
            'related_columns' => static::RELATED_TEXT_SEARCH_COLUMNS
        ];
    }

    /**
     * Applies exact column filters on configured columns.
     * 
     * @param Builder $queryBuilder The Eloquent query builder instance
     * @param array $queryOptions The filter criteria to apply
     * @return void
     * @throws \InvalidArgumentException If an invalid column is used for filtering
     */
    protected function applyColumnFilters(Builder $queryBuilder, array $queryOptions): void
    {
        // Get all allowed filterable columns
        $allowedColumns = array_merge(
            static::DIRECT_FILTERABLE_COLUMNS,
            static::RELATED_FILTERABLE_COLUMNS
        );

        foreach ($queryOptions as $columnName => $filterValue) {
            // Skip reserved keys and null values
            if ($this->isReservedParameterKey($columnName)) {
                continue;
            }

            // Validate column is in allowed filterable columns (security check)
            if (!in_array($columnName, $allowedColumns)) {
                throw new \InvalidArgumentException(
                    "Filtered column '{$columnName}' is not declared in filterable columns. " .
                    "Allowed columns: " . implode(', ', $allowedColumns)
                );
            }

            if ($this->isRelatedColumn($columnName)) {
                // Related model column: relation.column or relation_column
                $relationParts = $this->parseRelationColumn($columnName);
                $queryBuilder->whereHas($relationParts['relation'], function (Builder $relationQuery) use ($relationParts, $filterValue) {
                    if (is_array($filterValue)) {
                        $relationQuery->whereIn($relationParts['column'], $filterValue);
                    } else {
                        $relationQuery->where($relationParts['column'], $filterValue);
                    }
                });
            } else {
                // Direct column filter
                if (is_array($filterValue)) {
                    $queryBuilder->whereIn($columnName, $filterValue);
                } else {
                    $queryBuilder->where($columnName, $filterValue);
                }
            }
        }
    }

    /**
     * Applies sorting to the query based on provided options.
     * 
     * @param Builder $queryBuilder The Eloquent query builder instance
     * @param array $queryOptions Array containing sortBy and sortDirection parameters
     * @return void
     * @throws \InvalidArgumentException If an invalid column is used for sorting
     */
    protected function applySorting(Builder $queryBuilder, array $queryOptions): void
    {
        $sortColumn = strtolower($queryOptions[static::SORT_BY_PARAM] ?? static::DEFAULT_SORT_COLUMN);
        $sortDirection = strtolower($queryOptions[static::SORT_DIRECTION_PARAM] ?? static::DEFAULT_SORT_DIRECTION);

        // Get all allowed sortable columns
        $allowedColumns = array_merge(
            static::DIRECT_SORTABLE_COLUMNS,
            static::RELATED_SORTABLE_COLUMNS
        );

        // Validate sort column is in allowed sortable columns
        if (!in_array($sortColumn, $allowedColumns)) {
            throw new \InvalidArgumentException(
                "Sort column '{$sortColumn}' is not declared in sortable columns. " .
                "Allowed columns: " . implode(', ', $allowedColumns)
            );
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            return;
        }

        if ($this->isRelatedColumn($sortColumn)) {
            // Sort by related column logic (requires join)
            $relationParts = $this->parseRelationColumn($sortColumn);
            $mainTable = $this->model->getTable();
            $relatedModel = $this->model->{$relationParts['relation']}()->getRelated();
            $relatedTable = $relatedModel->getTable();
            $foreignKey = $this->model->{$relationParts['relation']}()->getForeignKeyName();
            $ownerKey = $this->model->{$relationParts['relation']}()->getOwnerKeyName();

            $queryBuilder->leftJoin($relatedTable, "{$mainTable}.{$foreignKey}", '=', "{$relatedTable}.{$ownerKey}")
                         ->orderBy("{$relatedTable}.{$relationParts['column']}", $sortDirection)
                         ->select("{$mainTable}.*");
        } else {
            // Direct column sorting
            $queryBuilder->orderBy($sortColumn, $sortDirection);
        }
    }
    
    // ========================================================================
    // --- Column Helper Methods ---
    // ========================================================================
    
    
    /**
     * Checks if a column is a direct column by checking against declared constants.
     * 
     * @param string $column The column name to check
     * @return bool True if it's a direct column
     */
    protected function isDirectColumn(string $column): bool
    {
        $allDirectColumns = array_merge(
            static::DIRECT_TEXT_SEARCH_COLUMNS,
            static::DIRECT_FILTERABLE_COLUMNS,
            static::DIRECT_SORTABLE_COLUMNS
        );
        
        return in_array($column, $allDirectColumns);
    }
    
    /**
     * Checks if a column is a related column by checking against declared constants.
     * 
     * @param string $column The column name to check
     * @return bool True if it's a related column
     */
    protected function isRelatedColumn(string $column): bool
    {
        $allRelatedColumns = array_merge(
            static::RELATED_TEXT_SEARCH_COLUMNS,
            static::RELATED_FILTERABLE_COLUMNS,
            static::RELATED_SORTABLE_COLUMNS
        );
        
        return in_array($column, $allRelatedColumns);
    }
    
    /**
     * Parses a relation column string to extract relation and column names.
     * 
     * @param string $columnString The column string to parse
     * @return array Array with 'relation' and 'column' keys
     */
    protected function parseRelationColumn(string $columnString): array
    {
        // Find the last occurrence of either dot or underscore
        $lastDotPos = strrpos($columnString, '.');
        $lastUnderscorePos = strrpos($columnString, '_');
        
        // Determine which separator comes last
        if ($lastDotPos === false && $lastUnderscorePos === false) {
            // No separators found, treat as direct column
            return ['relation' => '', 'column' => $columnString];
        }
        
        if ($lastDotPos === false) {
            // Only underscore found
            $splitPos = $lastUnderscorePos;
        } elseif ($lastUnderscorePos === false) {
            // Only dot found
            $splitPos = $lastDotPos;
        } else {
            // Both found, use the last one
            $splitPos = max($lastDotPos, $lastUnderscorePos);
        }
        
        $relation = substr($columnString, 0, $splitPos);
        $column = substr($columnString, $splitPos + 1);
        
        return [
            'relation' => $relation,
            'column' => $column
        ];
    }

    /**
     * Applies soft delete constraints based on configuration and filters.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options
     * @return void
     */
    protected function applySoftDeleteConstraints(Builder $query, array $filters): void
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive($this->model))) {
            return;
        }

        if (static::INCLUDE_SOFT_DELETED) {
            $query->withTrashed();
        } elseif (static::ONLY_SOFT_DELETED) {
            $query->onlyTrashed();
        }
    }

    /**
     * Applies field selection and exclusion.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options
     * @return void
     */
    protected function applyFieldSelection(Builder $query, array $filters): void
    {
        if (!empty(static::SELECT_COLUMNS)) {
            $query->select(static::SELECT_COLUMNS);
        }
        
        if (!empty(static::EXCLUDE_COLUMNS)) {
            $query->selectRaw('*');
            foreach (static::EXCLUDE_COLUMNS as $column) {
                $query->selectRaw("NULL as {$column}");
            }
        }
    }

    /**
     * Applies advanced filtering with operators.
     * 
     * @param Builder $queryBuilder The Eloquent query builder instance
     * @param array $queryOptions The filter criteria to apply
     * @return void
     * @throws \InvalidArgumentException If an invalid column is used for advanced filtering
     */
    protected function applyAdvancedFilters(Builder $queryBuilder, array $queryOptions): void
    {
        $advancedFilters = [
            'gte' => '>=', 'gt' => '>', 'lte' => '<=', 'lt' => '<',
            'like' => 'LIKE', 'not_like' => 'NOT LIKE',
            'in' => 'IN', 'not_in' => 'NOT IN',
            'between' => 'BETWEEN', 'not_between' => 'NOT BETWEEN',
            'is_null' => 'IS NULL', 'is_not_null' => 'IS NOT NULL'
        ];
        
        // Get all allowed filterable columns for validation
        $allowedColumns = array_merge(
            static::DIRECT_FILTERABLE_COLUMNS,
            static::RELATED_FILTERABLE_COLUMNS
        );
        
        foreach ($queryOptions as $columnName => $filterValue) {
            if ($this->isReservedParameterKey($columnName)) {
                continue;
            }

            if (!is_array($filterValue) || !isset($filterValue['operator'])) {
                continue;
            }
            
            // Validate column is in allowed filterable columns
            if (!in_array($columnName, $allowedColumns)) {
                throw new \InvalidArgumentException(
                    "Advanced filter column '{$columnName}' is not declared in filterable columns. " .
                    "Allowed columns: " . implode(', ', $allowedColumns)
                );
            }
            
            $operator = $filterValue['operator'];
            $value = $filterValue['value'] ?? null;
            
            if (isset($advancedFilters[$operator])) {
                $this->applyFilterOperator($queryBuilder, $columnName, $operator, $value);
            }
        }
    }

    /**
     * Applies a specific filter operator to the query.
     * 
     * @param Builder $queryBuilder The Eloquent query builder instance
     * @param string $columnName The column name
     * @param string $operator The operator to apply
     * @param mixed $value The value to filter by
     * @return void
     */
    protected function applyFilterOperator(Builder $queryBuilder, string $columnName, string $operator, $value): void
    {
        switch ($operator) {
            case 'gte':
                $queryBuilder->where($columnName, '>=', $value);
                break;
            case 'gt':
                $queryBuilder->where($columnName, '>', $value);
                break;
            case 'lte':
                $queryBuilder->where($columnName, '<=', $value);
                break;
            case 'lt':
                $queryBuilder->where($columnName, '<', $value);
                break;
            case 'like':
                $queryBuilder->where($columnName, 'LIKE', $value);
                break;
            case 'not_like':
                $queryBuilder->where($columnName, 'NOT LIKE', $value);
                break;
            case 'in':
                $queryBuilder->whereIn($columnName, (array) $value);
                break;
            case 'not_in':
                $queryBuilder->whereNotIn($columnName, (array) $value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $queryBuilder->whereBetween($columnName, $value);
                }
                break;
            case 'not_between':
                if (is_array($value) && count($value) === 2) {
                    $queryBuilder->whereNotBetween($columnName, $value);
                }
                break;
            case 'is_null':
                $queryBuilder->whereNull($columnName);
                break;
            case 'is_not_null':
                $queryBuilder->whereNotNull($columnName);
                break;
        }
    }

    /**
     * Overridable hook for adding custom query constraints.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options
     * @return void
     */
    protected function applyCustomQueryConstraints(Builder $query, array $filters): void
    {
        // Base implementation does nothing, override in child service.
    }

    // ========================================================================
    // --- Validation and Performance Monitoring ---
    // ========================================================================

    /**
     * Validates data for create or update operations.
     * 
     * @param array $data The data to validate
     * @param string $operation The operation type ('create' or 'update')
     * @return array The validated data
     * @throws ValidationException If validation fails
     */
    protected function validateData(array $data, string $operation = 'create'): array
    {
        $rules = $operation === 'create' ? static::CREATE_VALIDATION_RULES : static::UPDATE_VALIDATION_RULES;
        
        if (empty($rules)) {
            return $data;
        }
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $data;
    }


    /**
     * Transforms response using API resources if enabled.
     * 
     * @param mixed $data The data to transform
     * @param array $filters Array of query options
     * @return mixed The transformed data
     */
    protected function transformResponse($data, array $filters = []): mixed
    {
        if (!static::ENABLE_API_RESOURCES || !static::API_RESOURCE_CLASS) {
            return $data;
        }
        
        $resourceClass = static::API_RESOURCE_CLASS;
        
        if ($data instanceof LengthAwarePaginator) {
            return $data->through(fn($item) => new $resourceClass($item));
        }
        
        if ($data instanceof Collection) {
            return $data->map(fn($item) => new $resourceClass($item));
        }
        
        return new $resourceClass($data);
    }

    /**
     * Gets cache tags for more granular cache invalidation.
     * 
     * @return array Array of cache tags
     */
    protected function getCacheTags(): array
    {
        $tags = [$this->model->getTable()];
        
        if (!empty(static::CACHE_TAGS)) {
            $tags = array_merge($tags, static::CACHE_TAGS);
        }
        
        return $tags;
    }

    /**
     * Checks if a column name is a reserved parameter key.
     * 
     * @param string $columnName The column name to check
     * @return bool True if it's a reserved parameter key
     */
    protected function isReservedParameterKey(string $columnName): bool
    {
        return in_array($columnName, [
            static::SEARCH_PARAM,
            static::SORT_BY_PARAM,
            static::SORT_DIRECTION_PARAM,
            static::PAGINATE_PARAM,
            static::LIMIT_PARAM,
            static::MAGIC_COUNT
        ]);
    }

    // ========================================================================
    // --- Advanced Query Methods ---
    // ========================================================================

    /**
     * Processes large datasets using chunked processing.
     * 
     * @param array $filters Query options for filtering
     * @param callable|null $callback Callback function to process each chunk
     * @return void
     */
    public function findAllChunked(array $filters = [], callable $callback = null): void
    {
        if (!static::ENABLE_CHUNKED_PROCESSING) {
            return;
        }
        
        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
        $query->chunk(static::CHUNK_SIZE, function ($records) use ($callback) {
            if ($callback) {
                $callback($records);
            }
        });
    }

    // ========================================================================
    // --- Auditing and Cache Management ---
    // ========================================================================

    /**
     * Applies data modifications before creating a record.
     * 
     * @param array $data The original data array
     * @return array The modified data array
     */
    protected function prepareCreateData(array $data): array
    {
        if (static::AUDIT_TRAIL_ENABLED && Auth::check() && $this->model->isFillable(static::CREATOR_COLUMN)) {
            $data[static::CREATOR_COLUMN] = Auth::id();
        }
        return $data;
    }

    /**
     * Applies data modifications before updating a record.
     * 
     * @param array $data The original data array
     * @return array The modified data array
     */
    protected function prepareUpdateData(array $data): array
    {
        if (static::AUDIT_TRAIL_ENABLED && Auth::check() && $this->model->isFillable(static::UPDATER_COLUMN)) {
            $data[static::UPDATER_COLUMN] = Auth::id();
        }
        return $data;
    }

    /**
     * Applies data modifications before deleting a record.
     * 
     * @param Model $model The model being deleted
     * @return Model The modified model
     */
    protected function prepareDeleteData(Model $model): Model
    {
        if (static::AUDIT_TRAIL_ENABLED && Auth::check() && in_array(SoftDeletes::class, class_uses_recursive($model)) && $model->isFillable(static::DELETER_COLUMN)) {
            $model->{static::DELETER_COLUMN} = Auth::id();
            $model->save();
        }
        return $model;
    }
    
    /**
     * Clears the cache for this model's table.
     * 
     * @return void
     */
    protected function clearCache(): void
    {
        if (static::QUERY_CACHE_ENABLED) {
            try {
                // Use granular cache tags for more precise invalidation
                Cache::tags($this->getCacheTags())->flush();
            } catch (\Exception $e) {
                // Fallback to clearing all cache if tags don't work (e.g., in testing)
                Cache::flush();
            }
        }
    }

    // ========================================================================
    // --- Advanced Features Methods ---
    // ========================================================================

    /**
     * Gets search suggestions based on configured search columns.
     * 
     * @param string $term The search term
     * @param int $limit Maximum number of suggestions
     * @return Collection Collection of suggestions
     */
    public function getSearchSuggestions(string $term, int $limit = 10): Collection
    {
        $query = $this->createQueryBuilder();
        
        $suggestions = collect();
        
        foreach (static::DIRECT_TEXT_SEARCH_COLUMNS as $column) {
            $results = $query->select($column)
                            ->where($column, 'like', "%{$term}%")
                            ->distinct()
                            ->limit($limit)
                            ->pluck($column);
            
            $suggestions = $suggestions->merge($results);
        }
        
        return $suggestions->unique()->take($limit);
    }

    /**
     * Exports data using the specified format.
     * 
     * @param string $format The export format (csv, json, etc.)
     * @param array $filters Query options for filtering
     * @param array $columns Columns to export
     * @param array $options Additional export options
     * @return string Exported data
     * @throws \InvalidArgumentException If the format is not supported
     */
    public function export(string $format, array $filters = [], array $columns = [], array $options = []): string
    {
        $data = $this->getDataForExport($filters, $columns);
        $exportOptions = array_merge(['columns' => $columns], $options);
        
        return $this->exportService->export($format, $data, $exportOptions);
    }

    /**
     * Gets data for export operations.
     * 
     * @param array $filters Query options for filtering
     * @param array $columns Columns to select
     * @return Collection The data to export
     */
    protected function getDataForExport(array $filters, array $columns = []): Collection
    {
        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
        if (!empty($columns)) {
            $query->select($columns);
        }
        
        return $query->get();
    }

    /**
     * Adds a hook for a specific operation.
     * 
     * @param string $operation The operation name (e.g., 'before_find', 'after_create')
     * @param callable $hook The hook function
     * @return void
     */
    public function addHook(string $operation, callable $hook): void
    {
        $this->hookService->addHook($operation, new CallableHook($hook));
    }

    /**
     * Gets the search service instance.
     * 
     * @return SearchService The search service
     */
    public function getSearchService(): SearchService
    {
        return $this->searchService;
    }

    /**
     * Gets the export service instance.
     * 
     * @return ExportService The export service
     */
    public function getExportService(): ExportService
    {
        return $this->exportService;
    }

    /**
     * Gets the hook service instance.
     * 
     * @return HookService The hook service
     */
    public function getHookService(): HookService
    {
        return $this->hookService;
    }

    /**
     * Gets the query logger instance.
     * 
     * @return QueryLogger The query logger
     */
    public function getQueryLogger(): QueryLogger
    {
        return $this->queryLogger;
    }

    /**
     * Gets all declared filterable columns.
     * 
     * @return array Array of all filterable columns
     */
    public function getFilterableColumns(): array
    {
        return array_merge(
            static::DIRECT_FILTERABLE_COLUMNS,
            static::RELATED_FILTERABLE_COLUMNS
        );
    }

    /**
     * Gets all declared sortable columns.
     * 
     * @return array Array of all sortable columns
     */
    public function getSortableColumns(): array
    {
        return array_merge(
            static::DIRECT_SORTABLE_COLUMNS,
            static::RELATED_SORTABLE_COLUMNS
        );
    }

    /**
     * Gets all declared searchable columns.
     * 
     * @return array Array of all searchable columns
     */
    public function getSearchableColumns(): array
    {
        return array_merge(
            static::DIRECT_TEXT_SEARCH_COLUMNS,
            static::RELATED_TEXT_SEARCH_COLUMNS
        );
    }

    /**
     * Validates if a column is allowed for filtering.
     * 
     * @param string $column The column name to validate
     * @return bool True if the column is allowed for filtering
     */
    public function isColumnFilterable(string $column): bool
    {
        return in_array($column, $this->getFilterableColumns());
    }

    /**
     * Validates if a column is allowed for sorting.
     * 
     * @param string $column The column name to validate
     * @return bool True if the column is allowed for sorting
     */
    public function isColumnSortable(string $column): bool
    {
        return in_array($column, $this->getSortableColumns());
    }

    /**
     * Validates if a column is allowed for text search.
     * 
     * @param string $column The column name to validate
     * @return bool True if the column is allowed for text search
     */
    public function isColumnSearchable(string $column): bool
    {
        return in_array($column, $this->getSearchableColumns());
    }

}
