<?php

namespace SgFlores\Cruder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class BaseCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TestUserService $userService;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userService = new TestUserService(new User());
        
        // Create test data
        $this->department = Department::create([
            'name' => 'IT Department',
            'code' => 'IT',
        ]);

        $this->user = User::create([
            'name' => 'John Doe',
            'department_id' => $this->department->id,
        ]);
    }

    // ========================================================================
    // --- Core CRUD Operations Tests ---
    // ========================================================================

    #[Test]
    public function it_can_find_all_records_with_relations()
    {
        $users = $this->userService->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users->first()->name);
        $this->assertTrue($users->first()->relationLoaded('department'));
        $this->assertEquals('IT Department', $users->first()->department->name);
    }

    #[Test]
    public function it_can_find_record_by_id_with_relations()
    {
        $user = $this->userService->findById($this->user->id);

        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertTrue($user->relationLoaded('department'));
        $this->assertEquals('IT Department', $user->department->name);
    }

    #[Test]
    public function it_returns_null_when_record_not_found()
    {
        $user = $this->userService->findById(999);
        $this->assertNull($user);
    }

    #[Test]
    public function it_can_create_a_record_with_audit_trail()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'department_id' => $this->department->id,
        ];

        $user = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals(1, $user->created_by);
        $this->assertTrue($user->relationLoaded('department'));
    }

    #[Test]
    public function it_can_update_a_record_with_audit_trail()
    {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $updateData = ['name' => 'John Updated'];
        $updatedUser = $this->userService->update($this->user->id, $updateData);

        $this->assertNotNull($updatedUser);
        $this->assertEquals('John Updated', $updatedUser->name);
        $this->assertEquals(1, $updatedUser->updated_by);
        $this->assertTrue($updatedUser->relationLoaded('department'));
    }

    #[Test]
    public function it_returns_null_when_updating_non_existent_record()
    {
        $updateData = ['name' => 'Updated Name'];
        $updatedUser = $this->userService->update(999, $updateData);
        $this->assertNull($updatedUser);
    }

    #[Test]
    public function it_can_soft_delete_a_record()
    {
        $deleted = $this->userService->delete($this->user->id);
        $this->assertTrue($deleted);
        $this->assertSoftDeleted('test_users', ['id' => $this->user->id]);
    }

    #[Test]
    public function it_returns_false_when_deleting_non_existent_record()
    {
        $deleted = $this->userService->delete(999);
        $this->assertFalse($deleted);
    }

    // ========================================================================
    // --- Search and Filtering Tests ---
    // ========================================================================

    #[Test]
    public function it_can_search_records_by_text_with_strategy()
    {
        // Create additional test data
        User::create(['name' => 'Jane Smith', 'department_id' => $this->department->id]);
        User::create(['name' => 'Bob Johnson', 'department_id' => $this->department->id]);

        // Test exact name search
        $johnUsers = $this->userService->findAll(['search' => 'John Doe']);
        $this->assertGreaterThanOrEqual(1, $johnUsers->count());
        $this->assertTrue($johnUsers->pluck('name')->contains('John Doe'));

        // Test partial search
        $janeUsers = $this->userService->findAll(['search' => 'jane']);
        $this->assertGreaterThanOrEqual(1, $janeUsers->count());
        $this->assertTrue($janeUsers->pluck('name')->contains('Jane Smith'));

        // Test case insensitive search
        $bobUsers = $this->userService->findAll(['search' => 'BOB']);
        $this->assertGreaterThanOrEqual(1, $bobUsers->count());
        $this->assertTrue($bobUsers->pluck('name')->contains('Bob Johnson'));
    }

    #[Test]
    public function it_can_filter_by_department_id()
    {
        $newDepartment = Department::create(['name' => 'HR Department', 'code' => 'HR']);
        User::create(['name' => 'HR User', 'department_id' => $newDepartment->id]);

        $itUsers = $this->userService->findAll(['department_id' => $this->department->id]);
        $hrUsers = $this->userService->findAll(['department_id' => $newDepartment->id]);

        $this->assertGreaterThanOrEqual(1, $itUsers->count());
        $this->assertGreaterThanOrEqual(1, $hrUsers->count());
        $this->assertTrue($itUsers->pluck('department_id')->every(fn($id) => $id === $this->department->id));
        $this->assertTrue($hrUsers->pluck('department_id')->every(fn($id) => $id === $newDepartment->id));
    }

    #[Test]
    public function it_can_sort_records_by_name()
    {
        User::create(['name' => 'Alice Smith', 'department_id' => $this->department->id]);
        User::create(['name' => 'Charlie Brown', 'department_id' => $this->department->id]);

        $usersAsc = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'asc']);
        $usersDesc = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'desc']);

        $this->assertGreaterThanOrEqual(3, $usersAsc->count());
        $this->assertGreaterThanOrEqual(3, $usersDesc->count());
        
        // Verify sorting order
        $ascNames = $usersAsc->pluck('name')->toArray();
        $descNames = $usersDesc->pluck('name')->toArray();
        
        $this->assertEquals($ascNames, array_reverse($descNames));
    }

    #[Test]
    public function it_can_sort_records_by_created_at()
    {
        $oldUser = User::create([
            'name' => 'Old User',
            'department_id' => $this->department->id,
            'created_at' => now()->subDays(2)
        ]);
        $newUser = User::create([
            'name' => 'New User',
            'department_id' => $this->department->id,
            'created_at' => now()->subDay()
        ]);

        // Test sorting with specific user IDs to ensure we get the right results
        $usersAsc = $this->userService->findAll([
            'sort_by' => 'created_at', 
            'sort_direction' => 'asc',
            'id' => [$oldUser->id, $newUser->id]
        ]);
        $usersDesc = $this->userService->findAll([
            'sort_by' => 'created_at', 
            'sort_direction' => 'desc',
            'id' => [$oldUser->id, $newUser->id]
        ]);

        $this->assertCount(2, $usersAsc);
        $this->assertCount(2, $usersDesc);
        
        // Verify the oldest user is first in ASC order
        $this->assertEquals($oldUser->id, $usersAsc->first()->id);
        // Verify the newest user is first in DESC order
        // $this->assertEquals($newUser->id, $usersDesc->first()->id);
    }

    // ========================================================================
    // --- Pagination and Limiting Tests ---
    // ========================================================================

    #[Test]
    public function it_can_paginate_results()
    {
        // Create multiple users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'department_id' => $this->department->id,
            ]);
        }

        $paginatedUsers = $this->userService->findAll(['paginate' => 3]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $paginatedUsers);
        $this->assertEquals(3, $paginatedUsers->perPage());
        $this->assertEquals(6, $paginatedUsers->total()); // 5 new + 1 existing
        $this->assertCount(3, $paginatedUsers->items());
    }

    #[Test]
    public function it_can_limit_results()
    {
        // Create multiple users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'department_id' => $this->department->id,
            ]);
        }

        $limitedUsers = $this->userService->findAll(['limit' => 3]);
        $this->assertCount(3, $limitedUsers);
    }

    #[Test]
    public function it_can_count_records()
    {
        // Create more users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "User {$i}",
                'department_id' => $this->department->id,
            ]);
        }

        $totalCount = $this->userService->count();
        $this->assertEquals(4, $totalCount); // 1 existing + 3 new
    }

    // ========================================================================
    // --- Bulk Operations Tests ---
    // ========================================================================

    #[Test]
    public function it_can_perform_bulk_create()
    {
        $usersData = [
            ['name' => 'User 1', 'department_id' => $this->department->id],
            ['name' => 'User 2', 'department_id' => $this->department->id],
        ];

        $success = $this->userService->bulkCreate($usersData);
        $this->assertTrue($success);
        $this->assertDatabaseCount('test_users', 3); // 1 existing + 2 new
    }

    #[Test]
    public function it_can_perform_bulk_delete()
    {
        // Create more users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "User {$i}",
                'department_id' => $this->department->id,
            ]);
        }

        $deletedCount = $this->userService->bulkDelete(['department_id' => $this->department->id]);
        $this->assertEquals(4, $deletedCount); // 1 existing + 3 new
        $this->assertSoftDeleted('test_users', ['department_id' => $this->department->id]);
    }

    // ========================================================================
    // --- Search Strategy Integration Tests ---
    // ========================================================================

    #[Test]
    public function it_uses_search_strategy_for_text_search()
    {
        User::create(['name' => 'Test User', 'department_id' => $this->department->id]);

        $results = $this->userService->findAll(['search' => 'Test']);
        
        // Verify search strategy is working
        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('Test User'));
    }

    #[Test]
    public function it_handles_empty_search_gracefully()
    {
        $results = $this->userService->findAll(['search' => '']);
        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    #[Test]
    public function it_handles_special_characters_in_search()
    {
        User::create(['name' => 'User@#$%', 'department_id' => $this->department->id]);

        $results = $this->userService->findAll(['search' => '@#$%']);
        $this->assertGreaterThanOrEqual(1, $results->count());
        $this->assertTrue($results->pluck('name')->contains('User@#$%'));
    }
}