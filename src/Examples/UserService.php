<?php

namespace SgFlores\Cruder\Examples;

use App\Models\User;
use Illuminate\Support\Collection;
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
     * @param  User  $model  The User model instance
     * @param  EventService  $eventService  Event handling service
     * @param  ValidationService  $validationService  Validation service
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
     */
    public function isAuditTrailEnabled(): bool
    {
        return true;
    }

    /**
     * Define filterable columns for users.
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
            'updated_at',
        ];
    }

    /**
     * Define searchable columns for users.
     */
    public function getDirectTextSearchColumns(): array
    {
        return [
            'name',
            'email',
        ];
    }

    /**
     * Define sortable columns for users.
     */
    public function getDirectSortableColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Define related filterable columns.
     */
    public function getRelatedFilterableColumns(): array
    {
        return [
            'department.name',
            'department.status',
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
     */
    public function getRelatedTextSearchColumns(): array
    {
        return [
            'department.name',
        ];
    }

    /**
     * Define related sortable columns.
     */
    public function getRelatedSortableColumns(): array
    {
        return [
            'department.name',
        ];
    }

    /**
     * Define default relations to load for collections.
     */
    public function getCollectionRelations(): array
    {
        return [
            'department',
        ];
    }

    /**
     * Define default relations to load for single records.
     */
    public function getSingleRecordRelations(): array
    {
        return [
            'department',
            'logs',
        ];
    }

    /**
     * Enable query caching for better performance.
     */
    public function isQueryCacheEnabled(): bool
    {
        return true;
    }

    /**
     * Set cache lifetime to 30 minutes.
     */
    public function getCacheLifetimeSeconds(): int
    {
        return 1800; // 30 minutes
    }

    /**
     * Enable API resources for consistent response format.
     */
    public function shouldEnableApiResources(): bool
    {
        return true;
    }

    /**
     * Define the API resource class to use.
     */
    public function getApiResourceClass(): string
    {
        return 'App\\Http\\Resources\\UserResource';
    }

    /**
     * Enable chunked processing for large datasets.
     */
    public function shouldEnableChunkedProcessing(): bool
    {
        return true;
    }

    /**
     * Set chunk size for processing.
     */
    public function getChunkSize(): int
    {
        return 500;
    }

    /**
     * Override default sort column.
     */
    public function getDefaultSortColumn(): string
    {
        return 'name';
    }

    /**
     * Override default sort direction.
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
     * @return Collection
     */
    public function findByDepartment(int $departmentId, array $filters = [])
    {
        $filters['department_id'] = $departmentId;

        return $this->findAll($filters);
    }

    /**
     * Find active users.
     *
     * @return Collection
     */
    public function findActiveUsers(array $filters = [])
    {
        $filters['status'] = 'active';

        return $this->findAll($filters);
    }

    /**
     * Search users by name or email.
     *
     * @return Collection
     */
    public function searchUsers(string $term, array $filters = [])
    {
        $filters['search'] = $term;

        return $this->findAll($filters);
    }
}
