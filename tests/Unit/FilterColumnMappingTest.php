<?php

namespace SgFlores\Cruder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use SgFlores\Cruder\Tests\TestCase;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Services\TestUserService;

class FilterColumnMappingTest extends TestCase
{
    protected TestUserService $userService;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userService = new TestUserService(new User());
        $this->department = Department::factory()->create();
    }

    // ========================================================================
    // --- Filter Column Mapping Configuration Tests ---
    // ========================================================================

    #[Test]
    public function it_returns_empty_array_by_default(): void
    {
        // Test that default implementation returns empty array
        $this->assertEquals([], $this->userService->getFilterColumnMapping());
    }

    #[Test]
    public function it_can_be_overridden_in_child_service(): void
    {
        // Create a service that overrides getFilterColumnMapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };
        
        $mapping = $service->getFilterColumnMapping();
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('dept_name', $mapping);
        $this->assertEquals('department.name', $mapping['dept_name']);
    }

    // ========================================================================
    // --- Filter Transformation Tests ---
    // ========================================================================

    #[Test]
    public function it_transforms_filter_keys_using_mapping(): void
    {
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Create test users with department
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);

        // Test that filters are transformed and work correctly
        $result = $service->findAll([
            'dept_name' => $this->department->name, // Mapped to department.name
            'name' => 'User 1', // This should not be transformed (not in mapping)
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    #[Test]
    public function it_preserves_non_mapped_filter_keys(): void
    {
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Test that non-mapped keys are preserved
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        
        $result = $service->findAll([
            'dept_name' => $this->department->name, // Should be mapped to department.name
            'id' => $user1->id,                     // Should NOT be mapped (not in mapping)
            'name' => 'User 1',                     // Should NOT be mapped (not in mapping)
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    #[Test]
    public function it_handles_empty_mapping_gracefully(): void
    {
        // Service with empty mapping should work normally
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [];
            }
        };

        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        
        $result = $service->findAll([
            'id' => $user1->id,
            'name' => 'User 1',
        ]);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_supports_array_values_in_mapped_filters(): void
    {
        // Create another department for testing
        $department2 = Department::factory()->create(['name' => 'Engineering']);
        
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_names' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Create test users
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $department2->id]);

        // Test with array values (both single item and multiple items)
        $result1 = $service->findAll([
            'dept_names' => [$this->department->name], // Single item array
        ]);

        $result2 = $service->findAll([
            'dept_names' => [$this->department->name, $department2->name], // Multiple items array
        ]);

        $this->assertInstanceOf(Collection::class, $result1);
        $this->assertInstanceOf(Collection::class, $result2);
        $this->assertGreaterThanOrEqual(1, $result1->count());
        $this->assertGreaterThanOrEqual(2, $result2->count());
    }

    #[Test]
    public function it_supports_single_values_in_mapped_filters(): void
    {
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Create test user
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);

        // Test with single value (not array)
        $result = $service->findAll([
            'dept_name' => $this->department->name, // Single value
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    #[Test]
    public function it_respects_reserved_parameters_during_transformation(): void
    {
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Reserved parameters should not be transformed and should work normally
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        
        // Test without pagination (should return Collection)
        $result1 = $service->findAll([
            'dept_name' => $this->department->name,
            'search' => 'test',           // Reserved - should not be transformed
            'sort_by' => 'name',          // Reserved - should not be transformed
            'sort_direction' => 'asc',    // Reserved - should not be transformed
        ]);

        $this->assertInstanceOf(Collection::class, $result1);

        // Test with pagination (should return LengthAwarePaginator)
        $result2 = $service->findAll([
            'dept_name' => $this->department->name,
            'page' => 1,                  // Reserved - should not be transformed
            'per_page' => 10,             // Reserved - should not be transformed
        ]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result2);
    }

    #[Test]
    public function it_works_with_advanced_filters_after_mapping(): void
    {
        // Create a service with filter column mapping
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    'department.name',
                ];
            }
        };

        // Test that advanced filters work with mapped columns
        // Note: Advanced filters use internal column names, so this tests that
        // basic mapped filters don't interfere with advanced filter processing
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        
        $result = $service->findAll([
            'dept_name' => $this->department->name,    // Mapped filter
            'id' => ['operator' => 'gte', 'value' => 1], // Advanced filter (not mapped)
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(1, $result->count());
    }

    #[Test]
    public function it_validates_mapped_columns_against_related_filterable_columns(): void
    {
        // Create a service where mapping points to a column NOT in getRelatedFilterableColumns
        $service = new class(new User()) extends TestUserService {
            public function getFilterColumnMapping(): array
            {
                return [
                    'dept_name' => 'department.name',
                ];
            }

            public function getRelatedFilterableColumns(): array
            {
                return [
                    // 'department.name' is NOT included - should throw exception
                    'department.id',
                ];
            }
        };

        // This should throw an exception because 'department.name' is not in getRelatedFilterableColumns
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Filtered column 'department.name' is not declared in filterable columns");
        
        $service->findAll([
            'dept_name' => 'Test Department',
        ]);
    }
}

