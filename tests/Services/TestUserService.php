<?php

namespace SgFlores\Cruder\Tests\Services;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;
use SgFlores\Cruder\Tests\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TestUserService extends BaseCrudService
{
    public function __construct(User $user)
    {
        // Create services for testing
        $eventService = new EventService();
        
        // Pass only EventService to BaseCrudService constructor
        parent::__construct($user, $eventService);

        // Now manually inject the other services into the parent BaseReaderService
        $searchService = new SearchService();
        $exportService = new ExportService();
        $queryLogger = new QueryLogger();
        
        // Initialize services with strategies
        $searchService->addStrategy(LikeSearchStrategy::key(), new LikeSearchStrategy());
        $exportService->addStrategyByKey(new CsvExportStrategy());
        $exportService->addStrategyByKey(new JsonExportStrategy());
        
        // Manually set the services in the parent BaseReaderService
        $this->searchService = $searchService;
        $this->exportService = $exportService;
        $this->queryLogger = $queryLogger;

        // Add test event listeners (now using static constants)
        $eventService->listen(EventService::AFTER_CREATE, function ($user) {
            // Simulate sending welcome email
            return $user;
        });
        
        $eventService->listen(EventService::BEFORE_UPDATE, function ($data) {
            return $data;
        });
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

    public function getPageParam(): string
    {
        return 'page';
    }

    public function getPerPageParam(): string
    {
        return 'per_page';
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

    public function getSearchParam(): string
    {
        return 'search';
    }

    public function getSortByParam(): string
    {
        return 'sort_by';
    }

    public function getSortDirectionParam(): string
    {
        return 'sort_direction';
    }

    public function getDefaultSortColumn(): string
    {
        return 'id';
    }

    public function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public function getStrategiesParam(): string
    {
        return 'strategies';
    }

    public function getCustomFilterColumns(): array
    {
        return ['is_special_user'];
    }

    protected function applyCustomFilterColumn(Builder $queryBuilder, string $columnName, mixed $filterValue): void
    {
        if ($columnName === 'is_special_user') {
            $shouldBeSpecial = filter_var($filterValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($shouldBeSpecial === null) {
                return;
            }

            if ($shouldBeSpecial) {
                $queryBuilder->where('name', 'LIKE', 'Special%');
            } else {
                $queryBuilder->where('name', 'NOT LIKE', 'Special%');
            }
        }
    }
}