<?php

namespace SgFlores\Cruder\Tests\Services;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Tests\Models\User;

class TestUserService extends BaseCrudService
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    public function __construct()
    {
        parent::__construct(new User());

        // Add test event listeners
        $this->getEventService()->listen('after_create', function ($user) {
            // Simulate sending welcome email
            return $user;
        });
        
        $this->getEventService()->listen('before_update', function ($data) {
            return $data;
        });

        // add exports
        $this->getExportService()->addStrategy('csv', new CsvExportStrategy());
        $this->getExportService()->addStrategy('json', new JsonExportStrategy());
    }
    
    // Direct column constants
    protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name'];
    protected const DIRECT_FILTERABLE_COLUMNS = ['id', 'name', 'department_id', 'created_at', 'updated_at', 'deleted_at'];
    protected const DIRECT_SORTABLE_COLUMNS = ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];
    
    // Related column constants
    protected const RELATED_TEXT_SEARCH_COLUMNS = ['department_name'];
    protected const RELATED_FILTERABLE_COLUMNS = ['department_id'];
    protected const RELATED_SORTABLE_COLUMNS = ['department_name'];
    
    // Collection relations
    protected const COLLECTION_RELATIONS = ['department'];
    protected const SINGLE_RECORD_RELATIONS = ['department', 'createdBy', 'updatedBy'];
    
    // Search configuration

    protected const PAGINATE_PARAM = 'paginate';
    protected const LIMIT_PARAM = 'limit';
    
    // Audit trail
    protected const AUDIT_TRAIL_ENABLED = true;
    protected const CREATOR_COLUMN = 'created_by';
    protected const UPDATER_COLUMN = 'updated_by';
    protected const DELETER_COLUMN = 'deleted_by';

    
    // Cache configuration
    protected const QUERY_CACHE_ENABLED = true;
    protected const CACHE_LIFETIME_SECONDS = 3600;
    
    // Performance monitoring
    // Note: LOG_SLOW_QUERIES and SLOW_QUERY_THRESHOLD removed - now handled by QueryLogger config
    
    // Soft delete configuration
    protected const INCLUDE_SOFT_DELETED = false;
    protected const ONLY_SOFT_DELETED = false;
    
    // Field selection
    protected const SELECT_COLUMNS = [];
    protected const EXCLUDE_COLUMNS = [];
    
    // API resources
    protected const ENABLE_API_RESOURCES = false;
    protected const API_RESOURCE_CLASS = null;
    
    // Chunked processing
    protected const ENABLE_CHUNKED_PROCESSING = false;
    protected const CHUNK_SIZE = 1000;
    
    // Cache tags
    protected const CACHE_TAGS = ['users', 'test'];
    
    // Database connection
    protected const DATABASE_CONNECTION = null;
}