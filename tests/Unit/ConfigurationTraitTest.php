<?php

namespace SgFlores\Cruder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SgFlores\Cruder\Tests\TestCase;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;

class ConfigurationTraitTest extends TestCase
{
    protected TestUserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userService = new TestUserService(new User());
    }

    #[Test]
    public function it_uses_trait_configuration_methods(): void
    {
        // Test that the service uses the trait configuration methods
        $this->assertEquals(['name'], $this->userService->getDirectTextSearchColumns());
        $this->assertEquals(['id', 'name', 'department_id', 'created_at', 'updated_at', 'deleted_at'], $this->userService->getDirectFilterableColumns());
        $this->assertEquals(['id', 'name', 'created_at', 'updated_at', 'deleted_at'], $this->userService->getDirectSortableColumns());
        $this->assertEquals(['department_name'], $this->userService->getRelatedTextSearchColumns());
        $this->assertEquals(['department_id'], $this->userService->getRelatedFilterableColumns());
        $this->assertEquals(['department_name'], $this->userService->getRelatedSortableColumns());
        $this->assertEquals(['department'], $this->userService->getCollectionRelations());
        $this->assertEquals(['department', 'createdBy', 'updatedBy'], $this->userService->getSingleRecordRelations());
    }

    #[Test]
    public function it_uses_default_configuration_values(): void
    {
        // Test that the service uses default values from the trait
        $this->assertEquals('search', $this->userService->getSearchParam());
        $this->assertEquals('sort_by', $this->userService->getSortByParam());
        $this->assertEquals('sort_direction', $this->userService->getSortDirectionParam());
        $this->assertEquals('page', $this->userService->getPageParam());
        $this->assertEquals('per_page', $this->userService->getPerPageParam());
        $this->assertEquals('id', $this->userService->getDefaultSortColumn());
        $this->assertEquals('asc', $this->userService->getDefaultSortDirection());
        $this->assertTrue($this->userService->isQueryCacheEnabled());
        $this->assertEquals(3600, $this->userService->getCacheLifetimeSeconds());
        $this->assertEquals([], $this->userService->getSelectColumns());
        $this->assertEquals([], $this->userService->getExcludeColumns());
        $this->assertFalse($this->userService->shouldIncludeSoftDeleted());
        $this->assertFalse($this->userService->shouldOnlyShowSoftDeleted());
        $this->assertFalse($this->userService->shouldEnableApiResources());
        $this->assertNull($this->userService->getApiResourceClass());
        $this->assertFalse($this->userService->shouldEnableChunkedProcessing());
        $this->assertEquals(1000, $this->userService->getChunkSize());
        $this->assertNull($this->userService->getDatabaseConnection());
        $this->assertEquals([], $this->userService->getFilterColumnMapping());
    }

    #[Test]
    public function it_uses_crud_configuration_values(): void
    {
        // Test that the service uses CRUD-specific configuration values
        $this->assertTrue($this->userService->isAuditTrailEnabled());
        $this->assertEquals('created_by', $this->userService->getCreatorColumn());
        $this->assertEquals('updated_by', $this->userService->getUpdaterColumn());
        $this->assertEquals('deleted_by', $this->userService->getDeleterColumn());
    }

    #[Test]
    public function it_can_override_configuration_methods(): void
    {
        // Create a service that overrides configuration methods
        $service = new class(new User()) extends TestUserService {
            public function getDirectTextSearchColumns(): array
            {
                return ['name', 'email'];
            }
            
            public function isAuditTrailEnabled(): bool
            {
                return false;
            }
        };
        
        $this->assertEquals(['name', 'email'], $service->getDirectTextSearchColumns());
        $this->assertFalse($service->isAuditTrailEnabled());
    }

    #[Test]
    public function it_implements_configurable_interfaces(): void
    {
        // Test that the service implements the configurable interfaces
        $this->assertInstanceOf(\SgFlores\Cruder\Contracts\ReaderConfigurable::class, $this->userService);
        $this->assertInstanceOf(\SgFlores\Cruder\Contracts\CrudConfigurable::class, $this->userService);
    }
}
