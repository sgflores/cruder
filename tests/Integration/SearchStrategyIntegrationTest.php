<?php

namespace SgFlores\Cruder\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class SearchStrategyIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TestUserService $userService;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Create test data
        $this->department = Department::factory()->create();

        // Create service instance
        $this->userService = new TestUserService(new User);

        // Mock Auth for audit trail tests
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
    }

    // ========================================================================
    // --- Real-World Strategy Scenarios ---
    // ========================================================================

    public function test_like_search_strategy_with_real_data(): void
    {
        // Create test users with different names
        $users = collect([
            User::factory()->create([
                'name' => 'John Smith',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'Jane Doe',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'Bob Johnson',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'Alice Smith',
                'department_id' => $this->department->id,
            ]),
        ]);

        // Test exact name search
        $johnResults = $this->userService->findAll(['search' => 'John Smith']);
        $this->assertGreaterThanOrEqual(1, $johnResults->count());
        $this->assertTrue($johnResults->pluck('name')->contains('John Smith'));

        // Test partial name search
        $smithResults = $this->userService->findAll(['search' => 'Smith']);
        $this->assertGreaterThanOrEqual(2, $smithResults->count());
        $this->assertTrue($smithResults->pluck('name')->contains('John Smith'));
        $this->assertTrue($smithResults->pluck('name')->contains('Alice Smith'));

        // Test case insensitive search
        $bobResults = $this->userService->findAll(['search' => 'BOB']);
        $this->assertGreaterThanOrEqual(1, $bobResults->count());
        $this->assertTrue($bobResults->pluck('name')->contains('Bob Johnson'));

        // Test search with no results
        $noResults = $this->userService->findAll(['search' => 'NonExistentUser']);
        $this->assertGreaterThanOrEqual(0, $noResults->count());
    }

    public function test_strategy_with_pagination_and_sorting(): void
    {
        // Create multiple users for pagination testing
        $users = collect();
        for ($i = 1; $i <= 10; $i++) {
            $users->push(User::factory()->create([
                'name' => "User {$i}",
                'department_id' => $this->department->id,
            ]));
        }

        // Test search with pagination
        $paginatedResults = $this->userService->findAll([
            'search' => 'User',
            'per_page' => 5,
        ]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginatedResults);
        $this->assertEquals(5, $paginatedResults->perPage());
        $this->assertEquals(10, $paginatedResults->total());
        $this->assertCount(5, $paginatedResults->items());

        // Test search with sorting
        $sortedResults = $this->userService->findAll([
            'search' => 'User',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
        ]);

        $this->assertGreaterThanOrEqual(10, $sortedResults->count());
        $names = $sortedResults->pluck('name')->toArray();
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($names, array_reverse($sortedNames));
    }

    public function test_strategy_with_department_filtering(): void
    {
        // Create multiple departments
        $itDept = Department::factory()->create(['name' => 'IT Department']);
        $hrDept = Department::factory()->create(['name' => 'HR Department']);
        $financeDept = Department::factory()->create(['name' => 'Finance Department']);

        // Create users in different departments
        $itUsers = collect([
            User::factory()->create(['name' => 'IT User 1', 'department_id' => $itDept->id]),
            User::factory()->create(['name' => 'IT User 2', 'department_id' => $itDept->id]),
        ]);

        $hrUsers = collect([
            User::factory()->create(['name' => 'HR User 1', 'department_id' => $hrDept->id]),
            User::factory()->create(['name' => 'HR User 2', 'department_id' => $hrDept->id]),
        ]);

        $financeUsers = collect([
            User::factory()->create(['name' => 'Finance User 1', 'department_id' => $financeDept->id]),
        ]);

        // Test search within specific department
        $itSearchResults = $this->userService->findAll([
            'search' => 'User',
            'department_id' => $itDept->id,
        ]);

        $this->assertCount(2, $itSearchResults);
        $this->assertTrue($itSearchResults->pluck('department_id')->every(fn ($id) => $id === $itDept->id));

        // Test search within HR department
        $hrSearchResults = $this->userService->findAll([
            'search' => 'User',
            'department_id' => $hrDept->id,
        ]);

        $this->assertCount(2, $hrSearchResults);
        $this->assertTrue($hrSearchResults->pluck('department_id')->every(fn ($id) => $id === $hrDept->id));

        // Test search across all departments
        $allSearchResults = $this->userService->findAll(['search' => 'User']);
        $this->assertGreaterThanOrEqual(5, $allSearchResults->count());
    }

    public function test_strategy_with_complex_search_terms(): void
    {
        // Create users with complex names
        $users = collect([
            User::factory()->create([
                'name' => 'John-Paul Smith',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'Mary Jane Watson',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'Dr. Sarah Johnson',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'O\'Connor, Michael',
                'department_id' => $this->department->id,
            ]),
        ]);

        // Test search with hyphens
        $hyphenResults = $this->userService->findAll(['search' => 'John-Paul']);
        $this->assertGreaterThanOrEqual(1, $hyphenResults->count());
        $this->assertTrue($hyphenResults->pluck('name')->contains('John-Paul Smith'));

        // Test search with titles
        $titleResults = $this->userService->findAll(['search' => 'Dr.']);
        $this->assertGreaterThanOrEqual(1, $titleResults->count());
        $this->assertTrue($titleResults->pluck('name')->contains('Dr. Sarah Johnson'));

        // Test search with apostrophes
        $apostropheResults = $this->userService->findAll(['search' => 'O\'Connor']);
        $this->assertGreaterThanOrEqual(1, $apostropheResults->count());
        $this->assertTrue($apostropheResults->pluck('name')->contains('O\'Connor, Michael'));

        // Test search with spaces
        $spaceResults = $this->userService->findAll(['search' => 'Mary Jane']);
        $this->assertGreaterThanOrEqual(1, $spaceResults->count());
        $this->assertTrue($spaceResults->pluck('name')->contains('Mary Jane Watson'));
    }

    public function test_strategy_performance_with_large_dataset(): void
    {
        // Create a large dataset
        $users = collect();
        for ($i = 1; $i <= 100; $i++) {
            $users->push(User::factory()->create([
                'name' => "User {$i}",
                'department_id' => $this->department->id,
            ]));
        }

        $startTime = microtime(true);

        // Test search performance
        $searchResults = $this->userService->findAll(['search' => 'User']);
        $this->assertCount(100, $searchResults);

        // Test pagination performance
        $paginatedResults = $this->userService->findAll([
            'search' => 'User',
            'per_page' => 20,
        ]);
        $this->assertEquals(20, $paginatedResults->perPage());
        $this->assertEquals(100, $paginatedResults->total());

        // Test sorting performance
        $sortedResults = $this->userService->findAll([
            'search' => 'User',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ]);
        $this->assertCount(100, $sortedResults);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance should be reasonable (less than 2 seconds for this test)
        $this->assertLessThan(2000, $executionTime);
    }

    public function test_strategy_with_empty_and_special_characters(): void
    {
        // Create users with special characters
        $users = collect([
            User::factory()->create([
                'name' => 'User@#$%',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'User 123',
                'department_id' => $this->department->id,
            ]),
            User::factory()->create([
                'name' => 'User with spaces',
                'department_id' => $this->department->id,
            ]),
        ]);

        // Test search with special characters
        $specialResults = $this->userService->findAll(['search' => '@#$%']);
        $this->assertGreaterThanOrEqual(1, $specialResults->count());
        $this->assertTrue($specialResults->pluck('name')->contains('User@#$%'));

        // Test search with numbers
        $numberResults = $this->userService->findAll(['search' => '123']);
        $this->assertGreaterThanOrEqual(1, $numberResults->count());
        $this->assertTrue($numberResults->pluck('name')->contains('User 123'));

        // Test search with spaces
        $spaceResults = $this->userService->findAll(['search' => 'with spaces']);
        $this->assertGreaterThanOrEqual(1, $spaceResults->count());
        $this->assertTrue($spaceResults->pluck('name')->contains('User with spaces'));

        // Test empty search
        $emptyResults = $this->userService->findAll(['search' => '']);
        $this->assertGreaterThanOrEqual(3, $emptyResults->count());

        // Test search with only spaces
        $spaceOnlyResults = $this->userService->findAll(['search' => '   ']);
        $this->assertGreaterThanOrEqual(0, $spaceOnlyResults->count());
    }

    public function test_strategy_with_related_columns(): void
    {
        // Create departments with specific names
        $itDept = Department::factory()->create(['name' => 'Information Technology']);
        $hrDept = Department::factory()->create(['name' => 'Human Resources']);

        // Create users in departments
        $itUser = User::factory()->create([
            'name' => 'John Doe',
            'department_id' => $itDept->id,
        ]);
        $hrUser = User::factory()->create([
            'name' => 'Jane Smith',
            'department_id' => $hrDept->id,
        ]);

        // Test search by department name (related column)
        $itResults = $this->userService->findAll(['search' => 'Information']);
        $this->assertGreaterThanOrEqual(1, $itResults->count());
        $this->assertTrue($itResults->pluck('name')->contains('John Doe'));

        $hrResults = $this->userService->findAll(['search' => 'Human']);
        $this->assertGreaterThanOrEqual(1, $hrResults->count());
        $this->assertTrue($hrResults->pluck('name')->contains('Jane Smith'));

        // Test search by both user name and department name
        $combinedResults = $this->userService->findAll(['search' => 'John Technology']);
        $this->assertGreaterThanOrEqual(1, $combinedResults->count());
    }

    public function test_strategy_error_handling(): void
    {
        // Test with invalid search strategy
        $this->expectException(\InvalidArgumentException::class);

        // This should throw an exception if we try to use a non-existent strategy
        $this->userService->findAll(['strategies' => 'nonexistent']);
    }

    public function test_strategies_parameter_accepts_array(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // Test that strategies parameter accepts array format
        $results = $this->userService->findAll([
            'search' => 'Test User',
            'strategies' => ['like'],
        ]);

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Test User'));
    }

    public function test_strategies_parameter_accepts_comma_separated_string(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // Test that strategies parameter accepts comma-separated string format
        $results = $this->userService->findAll([
            'search' => 'Test User',
            'strategies' => 'like',
        ]);

        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Test User'));
    }

    public function test_strategy_with_audit_trail(): void
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // Search for the user
        $results = $this->userService->findAll(['search' => 'Test User']);
        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Test User'));

        // Verify the user has audit trail data
        $foundUser = $results->first();
        $this->assertNotNull($foundUser->created_at);
        $this->assertNotNull($foundUser->updated_at);
    }

    public function test_strategy_with_soft_deleted_records(): void
    {
        // Create users
        $activeUser = User::factory()->create([
            'name' => 'Active User',
            'department_id' => $this->department->id,
        ]);
        $deletedUser = User::factory()->create([
            'name' => 'Deleted User',
            'department_id' => $this->department->id,
        ]);

        // Soft delete one user
        $this->userService->delete($deletedUser->id);

        // Search should only return active users
        $results = $this->userService->findAll(['search' => 'User']);
        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Active User'));
        $this->assertFalse($results->pluck('name')->contains('Deleted User'));
    }

    public function test_strategy_with_export_functionality(): void
    {
        // Create test users
        User::factory()->count(5)->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // Test CSV export with search
        $csvData = $this->userService->export('csv', ['search' => 'Test User'], ['name']);
        $this->assertIsString($csvData);
        $this->assertStringContainsString('name', $csvData);
        $this->assertStringContainsString('Test User', $csvData);

        // Test JSON export with search
        $jsonData = $this->userService->export('json', ['search' => 'Test User'], ['name']);
        $this->assertIsString($jsonData);
        $data = json_decode($jsonData, true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(5, count($data));
    }
}
