<?php

namespace SgFlores\Cruder\Examples;

use App\Models\User;
use SgFlores\Cruder\BaseCrudService;

/**
 * User CRUD Service Example
 * 
 * Demonstrates how to create a complete CRUD service using BaseCrudService.
 * Shows column validation, relations, and custom business logic.
 */
class UserCrudService extends BaseCrudService
{
    // ========================================================================
    // --- Column Validation Constants ---
    // ========================================================================
    
    /**
     * Direct database columns available for exact matching filters.
     */
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'id', 'name', 'email', 'status', 'created_at', 'updated_at'
    ];
    
    /**
     * Direct database columns available for fuzzy text search.
     */
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [
        'name', 'email'
    ];
    
    /**
     * Direct database columns available for result sorting.
     */
    protected const DIRECT_SORTABLE_COLUMNS = [
        'id', 'name', 'email', 'status', 'created_at', 'updated_at'
    ];
    
    /**
     * Related model columns available for exact matching filters.
     */
    protected const RELATED_FILTERABLE_COLUMNS = [
        'profile.bio', 'profile.phone', 'roles.name'
    ];
    
    /**
     * Related model columns available for fuzzy text search.
     */
    protected const RELATED_TEXT_SEARCH_COLUMNS = [
        'profile.bio', 'roles.name'
    ];
    
    /**
     * Related model columns available for result sorting.
     */
    protected const RELATED_SORTABLE_COLUMNS = [
        'profile.created_at', 'roles.name'
    ];
    
    // ========================================================================
    // --- Relations Configuration ---
    // ========================================================================
    
    /**
     * Default relations to eager load for collection queries.
     */
    protected const COLLECTION_RELATIONS = ['profile', 'roles'];
    
    /**
     * Default relations to eager load for single record queries.
     */
    protected const SINGLE_RECORD_RELATIONS = ['profile', 'roles', 'permissions'];
    
    // ========================================================================
    // --- Feature Configuration ---
    // ========================================================================
    
    /**
     * Enable query result caching for performance optimization.
     */
    protected const QUERY_CACHE_ENABLED = true;
    
    /**
     * Cache lifetime in seconds.
     */
    protected const CACHE_LIFETIME_SECONDS = 1800; // 30 minutes
    
    /**
     * Enable audit trail for record changes.
     */
    protected const AUDIT_TRAIL_ENABLED = true;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new User());
        $this->setupEventListeners();
    }
    
    /**
     * Configure custom services and strategies.
     * 
     * Override this method in child classes to add custom strategies,
     * services, or modify existing behavior.
     * 
     * @return void
     */
    protected function configureServices(): void
    {
        // Add custom search strategies
        // $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
        
        // Add export strategies (optional - not included by default)
        $this->exportService->addStrategy('csv', new \SgFlores\Cruder\Strategies\Export\CsvExportStrategy());
        $this->exportService->addStrategy('json', new \SgFlores\Cruder\Strategies\Export\JsonExportStrategy());
        
        // Add custom export strategies
        // $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    }
    
    /**
     * Setup event listeners for business logic.
     */
    protected function setupEventListeners(): void
    {
        // Before create: validate business rules
        $this->getEventService()->listen('before_create', function ($data) {
            // Check if email is unique
            // Send welcome email preparation
            // Log user creation attempt
        });
        
        // After create: post-creation tasks
        $this->getEventService()->listen('after_create', function ($user) {
            // Send welcome email
            // Create user profile
            // Assign default role
            // Log successful creation
        });
        
        // Before update: validate changes
        $this->getEventService()->listen('before_update', function ($data) {
            // Check if email change is allowed
            // Validate role changes
            // Log update attempt
        });
        
        // After update: post-update tasks
        $this->getEventService()->listen('after_update', function ($user) {
            // Update related records
            // Send notification of changes
            // Log successful update
        });
        
        // Before delete: check dependencies
        $this->getEventService()->listen('before_delete', function ($user) {
            // Check if user has active orders
            // Check if user is last admin
            // Log deletion attempt
        });
        
        // After delete: cleanup tasks
        $this->getEventService()->listen('after_delete', function ($user) {
            // Soft delete related records
            // Send account deletion notification
            // Log successful deletion
        });
    }
    
    /**
     * Override to add custom query constraints.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyCustomQueryConstraints($query, array $filters): void
    {
        // Only show active users by default
        if (!isset($filters['include_inactive'])) {
            $query->where('status', 'active');
        }
        
        // Filter by role if specified
        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }
        
        // Filter by date range if specified
        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        
        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }
    }
    
    /**
     * Get users by role.
     * 
     * @param string $roleName
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getUsersByRole(string $roleName, array $filters = []): \Illuminate\Support\Collection
    {
        $filters['role'] = $roleName;
        return $this->findAll($filters);
    }
    
    /**
     * Get active users count.
     * 
     * @return int
     */
    public function getActiveUsersCount(): int
    {
        return $this->count(['status' => 'active']);
    }
    
    /**
     * Activate user account.
     * 
     * @param int|string $userId
     * @return bool
     */
    public function activateUser($userId): bool
    {
        $result = $this->update($userId, ['status' => 'active']);
        return $result !== null;
    }
    
    /**
     * Deactivate user account.
     * 
     * @param int|string $userId
     * @return bool
     */
    public function deactivateUser($userId): bool
    {
        $result = $this->update($userId, ['status' => 'inactive']);
        return $result !== null;
    }
}
