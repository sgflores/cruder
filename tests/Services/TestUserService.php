<?php

namespace SgFlores\Cruder\Tests\Services;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Tests\Models\User;

class TestUserService extends BaseCrudService
{
    public function __construct(User $user)
    {
        parent::__construct($user);

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

    // ========================================================================
    // --- Override Configuration Methods ---
    // ========================================================================

    public function getDirectTextSearchColumns(): array
    {
        return ['name'];
    }

    public function getDirectFilterableColumns(): array
    {
        return ['id', 'name', 'department_id', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function getDirectSortableColumns(): array
    {
        return ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function getRelatedTextSearchColumns(): array
    {
        return ['department_name'];
    }

    public function getRelatedFilterableColumns(): array
    {
        return ['department_id'];
    }

    public function getRelatedSortableColumns(): array
    {
        return ['department_name'];
    }

    public function getCollectionRelations(): array
    {
        return ['department'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['department', 'createdBy', 'updatedBy'];
    }

    public function getPaginateParam(): string
    {
        return 'paginate';
    }

    public function getLimitParam(): string
    {
        return 'limit';
    }

    public function isAuditTrailEnabled(): bool
    {
        return true;
    }

    public function getCreatorColumn(): string
    {
        return 'created_by';
    }

    public function getUpdaterColumn(): string
    {
        return 'updated_by';
    }

    public function getDeleterColumn(): string
    {
        return 'deleted_by';
    }

    public function isQueryCacheEnabled(): bool
    {
        return true;
    }

    public function getCacheLifetimeSeconds(): int
    {
        return 3600;
    }

    public function shouldIncludeSoftDeleted(): bool
    {
        return false;
    }

    public function shouldOnlyShowSoftDeleted(): bool
    {
        return false;
    }

    public function getSelectColumns(): array
    {
        return [];
    }

    public function getExcludeColumns(): array
    {
        return [];
    }

    public function shouldEnableApiResources(): bool
    {
        return false;
    }

    public function getApiResourceClass(): ?string
    {
        return null;
    }

    public function shouldEnableChunkedProcessing(): bool
    {
        return false;
    }

    public function getChunkSize(): int
    {
        return 1000;
    }


    public function getDatabaseConnection(): ?string
    {
        return null;
    }
}