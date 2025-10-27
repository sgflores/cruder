<?php

namespace SgFlores\Cruder\Tests\Unit;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use SgFlores\Cruder\Tests\TestCase;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;
use SgFlores\Cruder\Tests\Services\TestUserService;

class ColumnValidationTest extends TestCase
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
    // --- Column Validation Tests ---
    // ========================================================================

    public function test_throws_exception_for_invalid_filter_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Filtered column 'invalid_column' is not declared in filterable columns");
        
        $this->userService->findAll([
            'invalid_column' => 'some_value'
        ]);
    }

    public function test_throws_exception_for_invalid_sort_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort column 'invalid_column' is not declared in sortable columns");
        
        $this->userService->findAll([
            'sort_by' => 'invalid_column'
        ]);
    }

    public function test_throws_exception_for_invalid_advanced_filter_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Advanced filter column 'invalid_column' is not declared in filterable columns");
        
        $this->userService->findAll([
            'invalid_column' => [
                'operator' => 'gte',
                'value' => 100
            ]
        ]);
    }

    // ========================================================================
    // --- Advanced Filtering Tests ---
    // ========================================================================

    public function test_advanced_filter_gte_operator(): void
    {
        User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'gte', 'value' => 1]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    public function test_advanced_filter_gt_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'gt', 'value' => $user1->id]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($user2->id, $result->first()->id);
    }

    public function test_advanced_filter_lte_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'lte', 'value' => $user1->id]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($user1->id, $result->first()->id);
    }

    public function test_advanced_filter_lt_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'lt', 'value' => $user2->id]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($user1->id, $result->first()->id);
    }

    public function test_advanced_filter_like_operator(): void
    {
        User::factory()->create(['name' => 'John Doe', 'department_id' => $this->department->id]);
        User::factory()->create(['name' => 'Jane Smith', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'name' => ['operator' => 'like', 'value' => '%John%']
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result->first()->name);
    }

    public function test_advanced_filter_not_like_operator(): void
    {
        User::factory()->create(['name' => 'John Doe', 'department_id' => $this->department->id]);
        User::factory()->create(['name' => 'Jane Smith', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'name' => ['operator' => 'not_like', 'value' => '%John%']
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals('Jane Smith', $result->first()->name);
    }

    public function test_advanced_filter_in_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        $user3 = User::factory()->create(['name' => 'User 3', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'in', 'value' => [$user1->id, $user2->id]]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $user1->id));
        $this->assertTrue($result->contains('id', $user2->id));
        $this->assertFalse($result->contains('id', $user3->id));
    }

    public function test_advanced_filter_not_in_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        $user3 = User::factory()->create(['name' => 'User 3', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'not_in', 'value' => [$user1->id, $user2->id]]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($user3->id, $result->first()->id);
    }

    public function test_advanced_filter_between_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        $user3 = User::factory()->create(['name' => 'User 3', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'between', 'value' => [$user1->id, $user2->id]]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $user1->id));
        $this->assertTrue($result->contains('id', $user2->id));
        $this->assertFalse($result->contains('id', $user3->id));
    }

    public function test_advanced_filter_not_between_operator(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        $user2 = User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        $user3 = User::factory()->create(['name' => 'User 3', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'id' => ['operator' => 'not_between', 'value' => [$user1->id, $user2->id]]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($user3->id, $result->first()->id);
    }

    public function test_advanced_filter_is_null_operator(): void
    {
        User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'deleted_at' => ['operator' => 'is_null', 'value' => null]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    public function test_advanced_filter_is_not_null_operator(): void
    {
        User::factory()->create(['name' => 'User 1', 'department_id' => $this->department->id]);
        User::factory()->create(['name' => 'User 2', 'department_id' => $this->department->id]);
        
        $result = $this->userService->findAll([
            'created_at' => ['operator' => 'is_not_null', 'value' => null]
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    // ========================================================================
    // --- Helper Method Tests ---
    // ========================================================================

    public function test_helper_methods_work_correctly(): void
    {
        // Test filterable columns
        $this->assertTrue($this->userService->isColumnFilterable('id'));
        $this->assertTrue($this->userService->isColumnFilterable('department_id'));
        $this->assertFalse($this->userService->isColumnFilterable('invalid_column'));
        
        // Test sortable columns
        $this->assertTrue($this->userService->isColumnSortable('name'));
        $this->assertTrue($this->userService->isColumnSortable('created_at'));
        $this->assertFalse($this->userService->isColumnSortable('invalid_column'));
        
        // Test searchable columns
        $this->assertTrue($this->userService->isColumnSearchable('name'));
        $this->assertTrue($this->userService->isColumnSearchable('department_name'));
        $this->assertFalse($this->userService->isColumnSearchable('invalid_column'));
        
        // Test getter methods
        $filterableColumns = $this->userService->getFilterableColumns();
        $this->assertContains('id', $filterableColumns);
        $this->assertContains('department_id', $filterableColumns);
        
        $sortableColumns = $this->userService->getSortableColumns();
        $this->assertContains('name', $sortableColumns);
        $this->assertContains('created_at', $sortableColumns);
        
        $searchableColumns = $this->userService->getSearchableColumns();
        $this->assertContains('name', $searchableColumns);
        $this->assertContains('department_name', $searchableColumns);
    }

    public function test_allows_valid_filter_columns(): void
    {
        $result = $this->userService->findAll([
            'id' => [1, 2, 3],
            'department_id' => $this->department->id
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_allows_valid_sort_columns(): void
    {
        $result = $this->userService->findAll([
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_allows_valid_search_columns(): void
    {
        $result = $this->userService->findAll([
            'search' => 'test'
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_ignores_reserved_parameters(): void
    {
        // Test with per_page (paginated result)
        $result = $this->userService->findAll([
            'search' => 'test',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 10
        ]);
        
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_error_messages_include_allowed_columns(): void
    {
        try {
            $this->userService->findAll([
                'invalid_column' => 'some_value'
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("Filtered column 'invalid_column' is not declared in filterable columns", $e->getMessage());
            $this->assertStringContainsString("Allowed columns:", $e->getMessage());
            $this->assertStringContainsString("id", $e->getMessage());
            $this->assertStringContainsString("department_id", $e->getMessage());
        }
    }

    public function test_sort_error_messages_include_allowed_columns(): void
    {
        try {
            $this->userService->findAll([
                'sort_by' => 'invalid_column'
            ]);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString("Sort column 'invalid_column' is not declared in sortable columns", $e->getMessage());
            $this->assertStringContainsString("Allowed columns:", $e->getMessage());
            $this->assertStringContainsString("name", $e->getMessage());
            $this->assertStringContainsString("created_at", $e->getMessage());
        }
    }
}