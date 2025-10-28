<?php

namespace SgFlores\Cruder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use SgFlores\Cruder\Services\ValidationService;
use SgFlores\Cruder\Services\ValidationFactory;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Contracts\CrudConfigurable;
use SgFlores\Cruder\Traits\CrudConfigurationTrait;
use SgFlores\Cruder\Traits\PerformanceMonitoringTrait;
use SgFlores\Cruder\Exceptions\ValidationException as CruderValidationException;
use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Services\SearchService;

/**
 * Base CRUD Service - Complete CRUD Operations Foundation
 * 
 * This abstract class extends BaseReaderService to provide comprehensive CRUD (Create, Read, Update, Delete)
 * operations for Laravel applications, implementing SOLID principles and modern PHP best practices.
 * 
 * ## Architecture Overview
 * 
 * ### Inheritance Hierarchy
 * - **Extends**: `BaseReaderService` (inherits all read operations)
 * - **Implements**: `CrudConfigurable` (CRUD-specific configuration)
 * - **Uses**: `CrudConfigurationTrait` (audit trail and CRUD configuration)
 * 
 * ### SOLID Principles Implementation
 * - **Single Responsibility**: Handles CRUD operations and data persistence
 * - **Open/Closed**: Extensible through strategies, traits, and configuration methods
 * - **Liskov Substitution**: Child classes can be substituted without breaking functionality
 * - **Interface Segregation**: Implements both ReaderConfigurable and CrudConfigurable interfaces
 * - **Dependency Inversion**: Depends on abstractions (interfaces) rather than concrete implementations
 * 
 * ### Design Patterns Used
 * - **Strategy Pattern**: For validation, search, and export functionality
 * - **Observer Pattern**: For event-driven hooks and listeners
 * - **Trait Pattern**: For configuration management and code reuse
 * - **Template Method Pattern**: For consistent CRUD operation flow
 * 
 * ## CRUD Operations
 * 
 * ### Single Record Operations
 * - **Create**: `create()` - Creates new records with validation and audit trail
 * - **Read**: Inherited from BaseReaderService (`findAll()`, `findById()`, etc.)
 * - **Update**: `update()` - Updates existing records with validation and audit trail
 * - **Delete**: `delete()` - Soft or hard deletes records with audit trail
 * 
 * ### Bulk Operations
 * - **Bulk Create**: `bulkCreate()` - Creates multiple records efficiently
 * - **Bulk Update**: `bulkUpdate()` - Updates multiple records matching criteria
 * - **Bulk Delete**: `bulkDelete()` - Deletes multiple records matching criteria
 * 
 * ## Configuration System
 * 
 * The service uses a trait-based configuration system that provides:
 * - **Type Safety**: All configuration methods have proper return type hints
 * - **IDE Support**: Full autocompletion and IntelliSense support
 * - **Flexibility**: Methods can return computed values, not just constants
 * - **Override Capability**: Child classes can easily override any configuration
 * 
 * ### CRUD-Specific Configuration
 * - **Audit Trail**: `isAuditTrailEnabled()`, `getCreatorColumn()`, `getUpdaterColumn()`, `getDeleterColumn()`
 * - **Validation**: Built-in validation support with custom strategies
 * - **Events**: Before/after hooks for all CRUD operations
 * 
 * ## Security Features
 * 
 * ### Column Validation
 * Inherits all security features from BaseReaderService:
 * - Column validation for filtering, sorting, and searching
 * - Protection against unauthorized data access
 * - Clear error messages for invalid column usage
 * 
 * ### Audit Trail
 * Automatic tracking of record changes:
 * - **Creator**: Who created the record (`created_by`)
 * - **Updater**: Who last updated the record (`updated_by`)
 * - **Deleter**: Who soft-deleted the record (`deleted_by`)
 * 
 * ## Usage Example
 * 
 * ```php
 * use SgFlores\Cruder\Services\EventService;
 * use SgFlores\Cruder\Services\ValidationService;
 * 
 * class UserService extends BaseCrudService
 * {
 *     public function __construct(
 *         User $model,
 *         EventService $eventService,
 *         ValidationService $validationService
 *     ) {
 *         parent::__construct($model, $eventService, $validationService);
 *     }
 * 
 *     // Override configuration methods as needed
 *     public function getDirectFilterableColumns(): array
 *     {
 *         return ['id', 'name', 'email', 'status'];
 *     }
 * 
 *     public function isAuditTrailEnabled(): bool
 *     {
 *         return true;
 *     }
 * 
 *     public function getCreatorColumn(): string
 *     {
 *         return 'created_by';
 *     }
 * }
 * ```
 * 
 * ## Event System
 * 
 * The service provides comprehensive event hooks for all CRUD operations using static constants:
 * - `EventService::BEFORE_CREATE`, `EventService::AFTER_CREATE`
 * - `EventService::BEFORE_UPDATE`, `EventService::AFTER_UPDATE`
 * - `EventService::BEFORE_DELETE`, `EventService::AFTER_DELETE`
 * - `EventService::BEFORE_BULK_CREATE`, `EventService::AFTER_BULK_CREATE`
 * - `EventService::BEFORE_BULK_UPDATE`, `EventService::AFTER_BULK_UPDATE`
 * - `EventService::BEFORE_BULK_DELETE`, `EventService::AFTER_BULK_DELETE`
 */
abstract class BaseCrudService extends BaseReaderService implements CrudConfigurable
{
    use PerformanceMonitoringTrait;
    use CrudConfigurationTrait;

    /**
     * Validation service for managing validation strategies.
     * 
     * @var ValidationService
     */
    protected ValidationService $validationService;

    /**
     * Constructor - Initializes the CRUD Service
     * 
     * Sets up the service with the provided model, event service, and validation service.
     * The BaseCrudService focuses on CRUD operations and doesn't require reader services.
     * 
     * @param Model $model The Eloquent model instance this service will operate on
     * @param EventService|null $eventService Optional event service instance
     * @param ValidationService|null $validationService Optional validation service 
     * @param SearchService|null $searchService Optional search service instance
     * @param ExportService|null $exportService Optional export service instance
     * @param QueryLogger|null $queryLogger Optional query logger instance
     */
    public function __construct(
        Model $model,
        ?EventService $eventService = null,
        ?ValidationService $validationService = null,
        ?SearchService $searchService = null,
        ?ExportService $exportService = null,
        ?QueryLogger $queryLogger = null
    ) {
        parent::__construct($model, $searchService, $exportService, $eventService, $queryLogger);
        $this->validationService = $validationService ?? new ValidationService();
    }

    // ========================================================================
    // --- Public CRUD Methods ---
    // ========================================================================
    
    /**
     * Creates a new record with validation, audit trail, and optional eager loading.
     * 
     * This method handles the complete creation workflow:
     * 1. Validates data using provided rules or strategies
     * 2. Applies audit trail fields (created_by) if enabled
     * 3. Creates the record in a database transaction
     * 4. Eager loads specified relations
     * 5. Fires before/after events
     * 6. Clears cache for fresh data
     * 
     * @param array $data The data to create the record with
     * @param mixed $withRelations Relations to eager load (boolean, array, string, or null)
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules or strategy
     * @return Model The created model with relations loaded
     * @throws \Exception If creation fails
     */
    public function create(array $data, $withRelations = null, $validationRules = null): Model
    {
        // Fire before_create event
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_CREATE, $data);
        }
        
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

                // 6. After creation, an event is fired for hooks or listeners.
                if ($this->eventService) {
                    $this->eventService->fire(EventService::AFTER_CREATE, $createdModel);
                }
            
                // 7. If relations are requested, the model is reloaded with those relations eager loaded.
                $relationsToLoad = $this->resolveRelationsToLoad($withRelations, $this->getSingleRecordRelations());
                if (!empty($relationsToLoad)) {
                    $primaryKey = $this->model->getKeyName();
                    $createdModel = $this->model->newQuery()
                        ->with($relationsToLoad)
                        ->where($primaryKey, $createdModel->{$primaryKey})
                        ->first();
                }
            
                // 8. The result is transformed (e.g., for API output or further processing).
                $result = $this->transformResponse($createdModel);
            
                // 9. The final created (and possibly transformed) model is returned.
                return $result;
            }, [
                'data' => $data,
                'with_relations' => $withRelations
            ]);
        });
    }

    /**
     * Updates an existing record with validation, audit trail, and optional eager loading.
     * 
     * This method handles the complete update workflow:
     * 1. Finds the existing record by primary key
     * 2. Validates data using provided rules or strategies
     * 3. Applies audit trail fields (updated_by) if enabled
     * 4. Updates the record in a database transaction
     * 5. Eager loads specified relations
     * 6. Fires before/after events
     * 7. Clears cache for fresh data
     * 
     * @param int|string $id The primary key value of the record to update
     * @param array $data The data to update the record with
     * @param mixed $withRelations Relations to eager load (boolean, array, string, or null)
     * @param array|ValidationStrategyInterface|null $validationRules Validation rules or strategy
     * @return Model|null The updated model with relations loaded, or null if not found
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
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_UPDATE, $data);
        }

        return DB::transaction(function () use ($existingModel, $data, $id, $primaryKey, $withRelations, $validationRules) {
            return $this->executeWithTimingAndCache('update', function () use ($existingModel, $data, $id, $primaryKey, $withRelations, $validationRules) {
                // Validate data using ValidationFactory
                $validatedData = ValidationFactory::createValidator($data, $validationRules, 'update', ['model' => $existingModel]);
                $processedData = $this->prepareUpdateData($validatedData);
                $existingModel->update($processedData);
                
                // Fire after_update event
                if ($this->eventService) {
                    $this->eventService->fire(EventService::AFTER_UPDATE, $existingModel);
                }

                // Re-load with relations using dynamic PK
                $relationsToLoad = $this->resolveRelationsToLoad($withRelations, $this->getSingleRecordRelations());
                $updatedModel = $this->model->newQuery()
                    ->with($relationsToLoad)
                    ->where($primaryKey, $id)
                    ->first();
            
                $result = $this->transformResponse($updatedModel);
                
                return $result;
            }, [
                'id' => $id,
                'data' => $data,
                'with_relations' => $withRelations
            ]);
        });
    }

    /**
     * Deletes a record with audit trail and optional force deletion.
     * 
     * This method handles the complete deletion workflow:
     * 1. Finds the existing record by primary key
     * 2. Applies audit trail fields (deleted_by) if soft delete is enabled
     * 3. Deletes the record (soft or hard) in a database transaction
     * 4. Fires before/after events
     * 5. Clears cache for fresh data
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
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_DELETE, $modelToDelete);
        }
        
        return DB::transaction(function () use ($modelToDelete, $force, $id) {
            return $this->executeWithTimingAndCache('delete', function () use ($modelToDelete, $force) {
                if (!$force && method_exists($modelToDelete, 'bootSoftDeletes')) {
                    $modelToDelete = $this->prepareDeleteData($modelToDelete);
                }
                $deletionSuccessful = $force ? (bool) $modelToDelete->forceDelete() : (bool) $modelToDelete->delete();
                
                if ($deletionSuccessful) {
                    // Fire after_delete event
                    if ($this->eventService) {
                        $this->eventService->fire(EventService::AFTER_DELETE, $modelToDelete);
                    }
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
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_BULK_CREATE, $data);
        }
        
        return DB::transaction(function () use ($data, $validationRules) {
            return $this->executeWithTimingAndCache('bulk_create', function () use ($data, $validationRules) {
                // Validate data if validation rules provided
                if ($validationRules !== null) {
                    $data = $this->validateData($data, 'bulk_create', $validationRules);
                }
                
                // Add auditing fields if enabled
                if ($this->isAuditTrailEnabled() && Auth::check() && $this->model->isFillable($this->getCreatorColumn())) {
                    $currentUserId = Auth::id();
                    $data = array_map(function ($recordItem) use ($currentUserId) {
                        $recordItem[$this->getCreatorColumn()] = $currentUserId;
                        $recordItem['created_at'] = now();
                        $recordItem['updated_at'] = now();
                        return $recordItem;
                    }, $data);
                }

                $success = $this->model->insert($data);
                if ($success) {
                    // Fire after_bulk_create event
                    if ($this->eventService) {
                        $this->eventService->fire(EventService::AFTER_BULK_CREATE, $data);
                    }
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
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_BULK_UPDATE, $data);
            $this->eventService->fire(EventService::BEFORE_BULK_UPDATE_FILTERS, $filters);
        }

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
                    if ($this->eventService) {
                        $this->eventService->fire(EventService::AFTER_BULK_UPDATE, [
                            'updated_count' => $updatedCount,
                            'filters' => $filters,
                            'data' => $processedData
                        ]);
                    }
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
        if ($this->eventService) {
            $this->eventService->fire(EventService::BEFORE_BULK_DELETE_FILTERS, $filters);
            $this->eventService->fire(EventService::BEFORE_BULK_DELETE, $force);
        }

        return DB::transaction(function () use ($filters, $force) {
            return $this->executeWithTimingAndCache('bulk_delete', function () use ($filters, $force) {
                $query = $this->createQueryBuilder();
                $query = $this->applyQueryFilters($query, $filters);
            
                if ($force) {
                    $deletedCount = $query->forceDelete();
                } else {
                    // Apply deleted_by logic before soft deleting
                    if ($this->isAuditTrailEnabled() && Auth::check() && in_array(SoftDeletes::class, class_uses_recursive($this->model)) && $this->model->isFillable($this->getDeleterColumn())) {
                        $query->update([
                            $this->getDeleterColumn() => Auth::id(),
                        ]);
                    }
                    
                    $deletedCount = $query->delete();
                }

                if ($deletedCount > 0) {
                    // Fire after_bulk_delete event
                    if ($this->eventService) {
                        $this->eventService->fire(EventService::AFTER_BULK_DELETE, [
                            'deleted_count' => $deletedCount,
                            'filters' => $filters,
                            'force' => $force
                        ]);
                    }
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
        if ($this->isAuditTrailEnabled() && Auth::check() && $this->model->isFillable($this->getCreatorColumn())) {
            $data[$this->getCreatorColumn()] = Auth::id();
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
        if ($this->isAuditTrailEnabled() && Auth::check() && $this->model->isFillable($this->getUpdaterColumn())) {
            $data[$this->getUpdaterColumn()] = Auth::id();
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
        if ($this->isAuditTrailEnabled() && Auth::check() && in_array(SoftDeletes::class, class_uses_recursive($model)) && $model->isFillable($this->getDeleterColumn())) {
            $model->{$this->getDeleterColumn()} = Auth::id();
            $model->save();
        }
        return $model;
    }
}
