<?php

namespace SgFlores\Cruder;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use SgFlores\Cruder\Services\ValidationService;
use SgFlores\Cruder\Services\ValidationFactory;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Traits\PerformanceMonitoring;
use SgFlores\Cruder\Exceptions\ValidationException as CruderValidationException;
use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * Base CRUD Service with SOLID principles implementation.
 * 
 * This abstract class extends BaseReaderService to provide full CRUD operations
 * following SOLID principles:
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
abstract class BaseCrudService extends BaseReaderService
{
    use PerformanceMonitoring;

    /**
     * Validation service for managing validation strategies.
     * 
     * @var ValidationService
     */
    protected $validationService;


    /**
     * Event service for managing events.
     * 
     * @var EventService
     */
    protected $eventService;

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
     * BaseCrudService constructor.
     * 
     * Initializes the service with the model and sets up the service dependencies.
     * Child classes can override configureServices() to customize behavior.
     * 
     * @param Model $model The Eloquent model instance to work with
     */
    public function __construct(Model $model)
    {
        parent::__construct($model);
        $this->validationService = new ValidationService();
        $this->eventService = new EventService();
    }

    // ========================================================================
    // --- Public CRUD Methods ---
    // ========================================================================
    
    /**
     * Creates a new record with optional eager loading of relations.
     * 
     * @param array $data The data to create the record with
     * @param mixed $withRelations Relations to eager load on the returned model (boolean, array, string, or null)
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules array or strategy interface
     * @return Model The created model
     * @throws \Exception If creation fails
     */
    public function create(array $data, $withRelations = null, $validationRules = null): Model
    {
        // Fire before_create event
        $this->eventService->fire('before_create', $data);
        
        // This block handles the creation of a new record in an atomic (transactional) way,
        // with support for validation and eager loading of relations.

        // 1. The entire creation process is wrapped in a database transaction to ensure atomicity.
        return DB::transaction(function () use ($data, $withRelations, $validationRules) {
            
            // 2. The actual creation logic is wrapped in a timing/caching utility for performance monitoring.
            return $this->executeWithTimingAndCache('create', function () use ($data, $withRelations, $validationRules) {

                // 3. Data is validated using the ValidationFactory, which applies rules or strategies.
                $validatedData = ValidationFactory::createValidator($data, $validationRules, 'create');

                // 4. The validated data is further prepared (e.g., audit fields like created_by) before saving.
            $processedData = $this->prepareCreateData($validatedData);

                // 5. The model is created in the database.
            $createdModel = $this->model->create($processedData);
            
                // 6. If relations are requested, the model is reloaded with those relations eager loaded.
            $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::SINGLE_RECORD_RELATIONS);
            if (!empty($relationsToLoad)) {
                $primaryKey = $this->model->getKeyName();
                $createdModel = $this->model->newQuery()
                                          ->with($relationsToLoad)
                                          ->where($primaryKey, $createdModel->{$primaryKey})
                                          ->first();
            }
            
                // 7. The result is transformed (e.g., for API output or further processing).
            $result = $this->transformResponse($createdModel);
            
                // 8. After creation, an event is fired for hooks or listeners.
                $this->eventService->fire('after_create', $result);
                
                // 9. The final created (and possibly transformed) model is returned.
                return $result;
            }, [
                'data' => $data,
                'with_relations' => $withRelations
            ]);
        });
    }

    /**
     * Updates an existing record by its primary key with optional eager loading.
     * 
     * @param int|string $id The primary key value of the record to update
     * @param array $data The data to update the record with
     * @param mixed $withRelations Relations to eager load on the returned model (boolean, array, string, or null)
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules array or strategy interface
     * @return Model|null The updated model or null if not found
     * @throws \Exception If update fails
     */
    public function update(int|string $id, array $data, $withRelations = null, $validationRules = null): ?Model
    {
        $primaryKey = $this->model->getKeyName();
        // Find without relations to speed up the transaction
        $existingModel = $this->model->newQuery()->where($primaryKey, $id)->first(); 
        if (!$existingModel) {
            return null;
        }

        // Fire before_update event
        $this->eventService->fire('before_update', $data);

        return DB::transaction(function () use ($existingModel, $data, $id, $primaryKey, $withRelations, $validationRules) {
            return $this->executeWithTimingAndCache('update', function () use ($existingModel, $data, $id, $primaryKey, $withRelations, $validationRules) {
                // Validate data using ValidationFactory
                $validatedData = ValidationFactory::createValidator($data, $validationRules, 'update', ['model' => $existingModel]);
            $processedData = $this->prepareUpdateData($validatedData);
            $existingModel->update($processedData);
            
            // Re-load with relations using dynamic PK
            $relationsToLoad = $this->resolveRelationsToLoad($withRelations, static::SINGLE_RECORD_RELATIONS);
            $updatedModel = $this->model->newQuery()
                               ->with($relationsToLoad)
                               ->where($primaryKey, $id)
                               ->first();
            
                $result = $this->transformResponse($updatedModel);
                
                // Fire after_update event
                $this->eventService->fire('after_update', $result);
                
                return $result;
            }, [
                'id' => $id,
                'data' => $data,
                'with_relations' => $withRelations
            ]);
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
        if (!$modelToDelete) {
            return false;
        }
        
        // Fire before_delete event
        $this->eventService->fire('before_delete', $modelToDelete);
        
        return DB::transaction(function () use ($modelToDelete, $force, $id) {
            return $this->executeWithTimingAndCache('delete', function () use ($modelToDelete, $force) {
            if (!$force && method_exists($modelToDelete, 'bootSoftDeletes')) {
                $modelToDelete = $this->prepareDeleteData($modelToDelete);
            }
            $deletionSuccessful = $force ? (bool) $modelToDelete->forceDelete() : (bool) $modelToDelete->delete();
            
            if ($deletionSuccessful) {
                    // Fire after_delete event
                    $this->eventService->fire('after_delete', $modelToDelete);
            }
                    
            return $deletionSuccessful;
            }, [
                'id' => $modelToDelete->getKey(),
                'force' => $force
            ]);
        });
    }

    // ========================================================================
    // --- Mass Actions ---
    // ========================================================================

    /**
     * Creates multiple records in a single optimized database operation.
     * 
     * @param array $data Array of data arrays for bulk creation
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules array or strategy interface
     * @return bool True if all records were created successfully
     * @throws \Exception If bulk creation fails
     */
    public function bulkCreate(array $data, $validationRules = null): bool
    {
        // Fire before_bulk_create event
        $this->eventService->fire('before_bulk_create', $data);
        
        return DB::transaction(function () use ($data, $validationRules) {
            return $this->executeWithTimingAndCache('bulk_create', function () use ($data, $validationRules) {
                // Validate data if validation rules provided
                if ($validationRules !== null) {
                    $data = $this->validateData($data, 'bulk_create', $validationRules);
                }
                
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
                        if ($success) {
                            // Fire after_bulk_create event
                            $this->eventService->fire('after_bulk_create', $data);
                        }
        return $success;
            }, [
                'data_count' => count($data)
            ]);
        });
    }

    /**
     * Updates multiple records matching the given filter criteria.
     * 
     * Check the count of records to update before performing the operation.
     * If the count is 0, do not perform the operation.
     * @param array $filters Filter options to determine which records to update
     * @param array $data The data to update all matching records with
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules array or strategy interface
     * @return int Number of records that were updated
     * @throws \Exception If bulk update fails
     */
    public function bulkUpdate(array $filters, array $data, $validationRules = null): int
    {
        $count = $this->count($filters);
        if ($count === 0) {
            return 0;
        }

        // Fire before_bulk_update events
        $this->eventService->fire('before_bulk_update', $data);
        $this->eventService->fire('before_bulk_update_filters', $filters);

        return DB::transaction(function () use ($filters, $data, $validationRules) {
            return $this->executeWithTimingAndCache('bulk_update', function () use ($filters, $data, $validationRules) {
                // Validate data if validation rules provided
                if ($validationRules !== null) {
                    $data = $this->validateData($data, 'bulk_update', $validationRules);
        }

        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
        $processedData = $this->prepareUpdateData($data); // Adds updated_by
        
        $updatedCount = $query->update($processedData);
                if ($updatedCount > 0) {
                    // Fire after_bulk_update event
                    $this->eventService->fire('after_bulk_update', [
                        'updated_count' => $updatedCount,
                        'filters' => $filters,
                        'data' => $processedData
                    ]);
                }
        return $updatedCount;
            }, [
                'filters' => $filters,
                'data_count' => count($data)
            ]);
        });
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

        // Fire before_bulk_delete events
        $this->eventService->fire('before_bulk_delete_filters', $filters);
        $this->eventService->fire('before_bulk_delete', $force);

        return DB::transaction(function () use ($filters, $force) {
            return $this->executeWithTimingAndCache('bulk_delete', function () use ($filters, $force) {
        $query = $this->createQueryBuilder();
        $query = $this->applyQueryFilters($query, $filters);
        
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

                if ($deletedCount > 0) {
                    // Fire after_bulk_delete event
                    $this->eventService->fire('after_bulk_delete', [
                        'deleted_count' => $deletedCount,
                        'filters' => $filters,
                        'force' => $force
                    ]);
                }
                return $deletedCount;
            }, [
                'filters' => $filters,
                'force' => $force
            ]);
        });
    }
    
    // ========================================================================
    // --- Validation and Performance Monitoring ---
    // ========================================================================

    /**
     * Validates data for create or update operations.
     * 
     * @param array $data The data to validate
     * @param string $operation The operation type ('create', 'update', 'bulk_create', 'bulk_update')
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules array or strategy interface
     * @param array $context Additional context for validation
     * @return array The validated data
     * @throws CruderValidationException If validation fails
     */
    protected function validateData(array $data, string $operation = 'create', $validationRules = null, array $context = []): array
    {
        // Use ValidationFactory for consistent validation handling
        if ($validationRules !== null) {
            return ValidationFactory::createValidator($data, $validationRules, $operation, $context);
        }

        // Try to use service-level validation strategy
        if ($this->validationService->getStrategy() !== null) {
            $result = $this->validationService->validate($data, $operation, $context);
            return is_array($result) ? $result : $data;
        }
        
        // Fallback to no validation
        return $data;
    }

    /**
     * Sets the validation strategy for this service.
     * 
     * @param ValidationStrategyInterface $strategy The validation strategy
     * @return self
     */
    public function setValidationStrategy(ValidationStrategyInterface $strategy): self
    {
        $this->validationService->setStrategy($strategy);
        return $this;
    }

    /**
     * Gets the validation service instance.
     * 
     * @return ValidationService The validation service
     */
    public function getValidationService(): ValidationService
    {
        return $this->validationService;
    }


    /**
     * Gets the event service instance.
     * 
     * @return EventService The event service
     */
    public function getEventService(): EventService
    {
        return $this->eventService;
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
}
