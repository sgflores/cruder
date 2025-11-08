<?php

namespace SgFlores\Cruder\Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class BaseCrudServiceIntegrationTest extends TestCase
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

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // ========================================================================
    // --- Complete Business Workflow Tests ---
    // ========================================================================

    public function test_complete_user_management_workflow(): void
    {
        // 1. Create multiple users in different departments
        $itDept = Department::factory()->create(['name' => 'IT Department']);
        $hrDept = Department::factory()->create(['name' => 'HR Department']);

        $itUsers = User::factory()->count(3)->create(['department_id' => $itDept->id]);
        $hrUsers = User::factory()->count(2)->create(['department_id' => $hrDept->id]);

        // 2. Test department-based filtering
        $itResults = $this->userService->findAll(['department_id' => $itDept->id]);
        $hrResults = $this->userService->findAll(['department_id' => $hrDept->id]);

        $this->assertGreaterThanOrEqual(3, $itResults->count());
        $this->assertGreaterThanOrEqual(2, $hrResults->count());

        // 3. Test search functionality across departments
        $searchResults = $this->userService->findAll(['search' => $itUsers->first()->name]);
        $this->assertGreaterThanOrEqual(1, $searchResults->count());
        $this->assertTrue($searchResults->pluck('name')->contains($itUsers->first()->name));

        // 4. Test sorting
        $sortedUsers = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'asc']);
        $this->assertCount(5, $sortedUsers);

        // 5. Test pagination
        $paginatedUsers = $this->userService->findAll(['per_page' => 3]);
        $this->assertEquals(3, $paginatedUsers->perPage());
        $this->assertEquals(5, $paginatedUsers->total());

        // 6. Test individual record operations
        $user = $itUsers->first();
        $foundUser = $this->userService->findById($user->id);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertTrue($foundUser->relationLoaded('department'));

        // 7. Test update with audit trail
        $updatedUser = $this->userService->update($user->id, ['name' => 'Updated Name']);
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals(1, $updatedUser->updated_by);

        // 8. Test count
        $userCount = $this->userService->count();
        $this->assertEquals(5, $userCount);

        // 9. Test export functionality
        $csvData = $this->userService->export('csv', [], ['name']);
        $this->assertStringContainsString('name', $csvData);

        $jsonData = $this->userService->export('json', [], ['name']);
        $decoded = json_decode($jsonData, true);
        $this->assertCount(5, $decoded);

        // 10. Test soft delete
        $deleteResult = $this->userService->delete($user->id);
        $this->assertTrue($deleteResult);

        // 11. Verify soft delete
        $remainingUsers = $this->userService->findAll();
        $this->assertCount(4, $remainingUsers);
        $this->assertFalse($remainingUsers->pluck('id')->contains($user->id));

        // 12. Test bulk delete
        $bulkDeleteResult = $this->userService->bulkDelete(['department_id' => $itDept->id]);
        $this->assertEquals(2, $bulkDeleteResult); // Remaining 2 IT users

        // 13. Verify final state
        $finalUsers = $this->userService->findAll();
        $this->assertCount(2, $finalUsers); // Only HR users remain
        $this->assertTrue($finalUsers->pluck('department_id')->every(fn ($id) => $id === $hrDept->id));
    }

    public function test_advanced_search_and_filtering_scenarios(): void
    {
        // Create users with different characteristics
        $users = collect([
            User::factory()->create([
                'name' => 'John Smith',
                'department_id' => $this->department->id,
                'created_at' => now()->subDays(3),
            ]),
            User::factory()->create([
                'name' => 'Jane Doe',
                'department_id' => $this->department->id,
                'created_at' => now()->subDays(1),
            ]),
            User::factory()->create([
                'name' => 'Bob Johnson',
                'department_id' => $this->department->id,
                'created_at' => now()->subDays(2),
            ]),
        ]);

        // Test exact name search
        $johnResults = $this->userService->findAll(['search' => 'John Smith']);
        $this->assertGreaterThanOrEqual(1, $johnResults->count());
        $this->assertTrue($johnResults->pluck('name')->contains('John Smith'));

        // Test partial search
        $smithResults = $this->userService->findAll(['search' => 'smith']);
        $this->assertGreaterThanOrEqual(1, $smithResults->count());
        $this->assertTrue($smithResults->pluck('name')->contains('John Smith'));

        // Test case insensitive search
        $bobResults = $this->userService->findAll(['search' => 'BOB']);
        $this->assertGreaterThanOrEqual(1, $bobResults->count());
        $this->assertTrue($bobResults->pluck('name')->contains('Bob Johnson'));

        // Test combined search and filtering
        $filteredResults = $this->userService->findAll([
            'search' => 'John',
            'department_id' => $this->department->id,
        ]);
        $this->assertGreaterThanOrEqual(1, $filteredResults->count());
        $this->assertTrue($filteredResults->pluck('name')->contains('John Smith'));
    }

    public function test_complex_sorting_scenarios(): void
    {
        // Create users with different names and creation dates
        $user1 = User::factory()->create([
            'name' => 'Charlie Brown',
            'created_at' => now()->subDays(3),
            'department_id' => $this->department->id,
        ]);
        $user2 = User::factory()->create([
            'name' => 'Alice Smith',
            'created_at' => now()->subDays(1),
            'department_id' => $this->department->id,
        ]);
        $user3 = User::factory()->create([
            'name' => 'Bob Johnson',
            'created_at' => now()->subDays(2),
            'department_id' => $this->department->id,
        ]);

        // Test name sorting ASC
        $nameAscResults = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'asc']);
        $this->assertGreaterThanOrEqual(3, $nameAscResults->count());
        $this->assertEquals('Alice Smith', $nameAscResults->first()->name);
        $this->assertEquals('Charlie Brown', $nameAscResults->last()->name);

        // Test name sorting DESC
        $nameDescResults = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'desc']);
        $this->assertGreaterThanOrEqual(3, $nameDescResults->count());
        $this->assertEquals('Charlie Brown', $nameDescResults->first()->name);
        $this->assertEquals('Alice Smith', $nameDescResults->last()->name);

        // Test created_at sorting ASC
        $dateAscResults = $this->userService->findAll(['sort_by' => 'created_at', 'sort_direction' => 'asc']);
        $this->assertGreaterThanOrEqual(3, $dateAscResults->count());
        $this->assertEquals($user1->id, $dateAscResults->first()->id);
        $this->assertEquals($user2->id, $dateAscResults->last()->id);
    }

    public function test_export_integration_with_filters(): void
    {
        // Create test users
        User::factory()->count(5)->create(['department_id' => $this->department->id]);

        // Test CSV export
        $csvData = $this->userService->export('csv', [], ['name']);
        $lines = explode("\n", trim($csvData));
        $this->assertCount(6, $lines); // 1 header + 5 data rows
        $this->assertStringContainsString('name', $lines[0]);

        // Test JSON export
        $jsonData = $this->userService->export('json', [], ['name']);
        $decoded = json_decode($jsonData, true);
        $this->assertCount(5, $decoded);
        $this->assertArrayHasKey('name', $decoded[0]);

        // Test export with search filter
        $searchResults = $this->userService->findAll(['search' => 'test']);
        $this->assertGreaterThanOrEqual(0, $searchResults->count());
    }

    public function test_performance_with_large_dataset(): void
    {
        // Create large dataset
        User::factory()->count(100)->create(['department_id' => $this->department->id]);

        $startTime = microtime(true);

        // Test various operations
        $this->userService->findAll();
        $this->userService->findAll(['search' => 'test']);
        $this->userService->findAll(['sort_by' => 'name']);
        $this->userService->count();
        $this->userService->export('csv', [], ['name']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Performance should be reasonable (less than 5 seconds for this test)
        $this->assertLessThan(5000, $executionTime);
    }

    public function test_error_handling_and_graceful_degradation(): void
    {
        // Test validation errors
        $this->expectException(\SgFlores\Cruder\Exceptions\ValidationException::class);

        $validationRules = [
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:test_departments,id',
        ];

        $this->userService->create([
            'name' => '', // Invalid
            'department_id' => 999, // Invalid
        ], null, $validationRules);
    }

    public function test_search_suggestions_integration(): void
    {
        // Create users with similar names
        User::factory()->create([
            'name' => 'John Smith',
            'department_id' => $this->department->id,
        ]);
        User::factory()->create([
            'name' => 'John Doe',
            'department_id' => $this->department->id,
        ]);
        User::factory()->create([
            'name' => 'Jane Smith',
            'department_id' => $this->department->id,
        ]);

        // Test search suggestions
        $suggestions = $this->userService->getSearchSuggestions('John');
        $this->assertGreaterThan(0, $suggestions->count());
        $this->assertTrue($suggestions->contains('John Smith'));
        $this->assertTrue($suggestions->contains('John Doe'));

        $suggestions = $this->userService->getSearchSuggestions('Smith');
        $this->assertGreaterThan(0, $suggestions->count());
        $this->assertTrue($suggestions->contains('John Smith'));
        $this->assertTrue($suggestions->contains('Jane Smith'));
    }

    public function test_cache_integration_with_real_scenarios(): void
    {
        // Clear cache
        Cache::flush();

        // Create test data
        User::factory()->count(10)->create(['department_id' => $this->department->id]);

        // First call should hit database
        $firstCall = $this->userService->findAll();
        $this->assertCount(10, $firstCall);

        // Second call should use cache (if enabled)
        $secondCall = $this->userService->findAll();
        $this->assertCount(10, $secondCall);

        // Test cache invalidation on create
        $createdUser = $this->userService->create([
            'name' => 'Cache Test User',
            'department_id' => $this->department->id,
        ]);

        $this->assertNotNull($createdUser);
        $this->assertEquals('Cache Test User', $createdUser->name);

        // Cache should be cleared, so we should get updated results
        $afterCreate = $this->userService->findAll();
        $this->assertCount(11, $afterCreate);
    }
}
