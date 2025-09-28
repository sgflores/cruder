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
        
        $this->userService = new TestUserService();
        $this->department = Department::factory()->create();
    }

    public function test_throws_exception_for_invalid_filter_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->userService->findAll([
            'invalid_column' => 'some_value'
        ]);
    }

    public function test_throws_exception_for_invalid_sort_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->userService->findAll([
            'sort_by' => 'invalid_column'
        ]);
    }

    public function test_throws_exception_for_invalid_advanced_filter_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->userService->findAll([
            'invalid_column' => [
                'operator' => 'gte',
                'value' => 100
            ]
        ]);
    }

    public function test_throws_exception_when_no_searchable_columns_declared(): void
    {
        // Create a service with no searchable columns
        $service = new class(new User()) extends TestUserService {
            protected const DIRECT_TEXT_SEARCH_COLUMNS = [];
            protected const RELATED_TEXT_SEARCH_COLUMNS = [];
        };
        
        $this->expectException(InvalidArgumentException::class);
        
        $service->findAll([
            'search' => 'test'
        ]);
    }

    public function test_allows_valid_filter_columns(): void
    {
        // This should not throw an exception
        $result = $this->userService->findAll([
            'id' => [1, 2, 3],
            'department_id' => $this->department->id
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

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

    public function test_allows_valid_sort_columns(): void
    {
        // This should not throw an exception
        $result = $this->userService->findAll([
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_allows_valid_search_columns(): void
    {
        // This should not throw an exception
        $result = $this->userService->findAll([
            'search' => 'test'
        ]);
        
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_ignores_reserved_parameters(): void
    {
        // These should not throw exceptions as they are reserved parameters
        $result = $this->userService->findAll([
            'search' => 'test',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'paginate' => 10,
            'limit' => 5
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
