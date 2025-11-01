<?php

namespace SgFlores\Cruder\Examples;

use App\Models\User;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;

/**
 * Example User Service implementation showing proper dependency injection patterns.
 * 
 * This example demonstrates how to override the default configuration methods
 * to customize the service behavior for a specific model with proper Laravel
 * dependency injection standards.
 */
class UserService extends BaseCrudService
{
    /**
     * UserService constructor.
     * 
     * @param User $model The User model instance
     * @param EventService $eventService Event handling service
     * @param ValidationService $validationService Validation service
     */
    public function __construct(
        User $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    // ========================================================================
    // --- Override Configuration Methods ---
    // ========================================================================

    /**
     * Enable audit trail for user operations.
     * 
     * @return bool
     */
    public function isAuditTrailEnabled(): bool
    {
        return true;
    }

    /**
     * Define filterable columns for users.
     * 
     * @return array
     */
    public function getDirectFilterableColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'status',
            'department_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Define searchable columns for users.
     * 
     * @return array
     */
    public function getDirectTextSearchColumns(): array
    {
        return [
            'name',
            'email'
        ];
    }

    /**
     * Define sortable columns for users.
     * 
     * @return array
     */
    public function getDirectSortableColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'status',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Define related filterable columns.
     * 
     * @return array
     */
    public function getRelatedFilterableColumns(): array
    {
        return [
            'department.name',
            'department.status'
        ];
    }

    /**
     * Map friendly request parameter keys to internal filterable column names.
     * 
     * This allows API consumers to use user-friendly parameter names (e.g., 'role_names')
     * while internally using proper column names (e.g., 'assignedRoles.name').
     * 
     * @return array Map of request parameter key => internal column name
     */
    public function getFilterColumnMapping(): array
    {
        return [
            'role_names' => 'assignedRoles.name',
            'branch_ids' => 'branches.id',
            // Add more mappings as needed
        ];
    }

    /**
     * Define related searchable columns.
     * 
     * @return array
     */
    public function getRelatedTextSearchColumns(): array
    {
        return [
            'department.name'
        ];
    }

    /**
     * Define related sortable columns.
     * 
     * @return array
     */
    public function getRelatedSortableColumns(): array
    {
        return [
            'department.name'
        ];
    }

    /**
     * Define default relations to load for collections.
     * 
     * @return array
     */
    public function getCollectionRelations(): array
    {
        return [
            'department'
        ];
    }

    /**
     * Define default relations to load for single records.
     * 
     * @return array
     */
    public function getSingleRecordRelations(): array
    {
        return [
            'department',
            'logs'
        ];
    }

    /**
     * Enable query caching for better performance.
     * 
     * @return bool
     */
    public function isQueryCacheEnabled(): bool
    {
        return true;
    }

    /**
     * Set cache lifetime to 30 minutes.
     * 
     * @return int
     */
    public function getCacheLifetimeSeconds(): int
    {
        return 1800; // 30 minutes
    }


    /**
     * Enable API resources for consistent response format.
     * 
     * @return bool
     */
    public function shouldEnableApiResources(): bool
    {
        return true;
    }

    /**
     * Define the API resource class to use.
     * 
     * @return string
     */
    public function getApiResourceClass(): string
    {
        return 'App\\Http\\Resources\\UserResource';
    }

    /**
     * Enable chunked processing for large datasets.
     * 
     * @return bool
     */
    public function shouldEnableChunkedProcessing(): bool
    {
        return true;
    }

    /**
     * Set chunk size for processing.
     * 
     * @return int
     */
    public function getChunkSize(): int
    {
        return 500;
    }

    /**
     * Override default sort column.
     * 
     * @return string
     */
    public function getDefaultSortColumn(): string
    {
        return 'name';
    }

    /**
     * Override default sort direction.
     * 
     * @return string
     */
    public function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    // ========================================================================
    // --- Custom Business Logic Methods ---
    // ========================================================================

    /**
     * Find users by department.
     * 
     * @param int $departmentId
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function findByDepartment(int $departmentId, array $filters = [])
    {
        $filters['department_id'] = $departmentId;
        return $this->findAll($filters);
    }

    /**
     * Find active users.
     * 
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function findActiveUsers(array $filters = [])
    {
        $filters['status'] = 'active';
        return $this->findAll($filters);
    }

    /**
     * Search users by name or email.
     * 
     * @param string $term
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function searchUsers(string $term, array $filters = [])
    {
        $filters['search'] = $term;
        return $this->findAll($filters);
    }
}
