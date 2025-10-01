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

    #[Test]
    public function it_can_find_all_records()
    {
        $users = $this->userService->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users->first()->name);
        $this->assertTrue($users->first()->relationLoaded('department'));
    }

    #[Test]
    public function it_can_find_record_by_id()
    {
        $user = $this->userService->findById($this->user->id);

        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertTrue($user->relationLoaded('department'));
    }

    #[Test]
    public function it_returns_null_when_record_not_found()
    {
        $user = $this->userService->findById(999);

        $this->assertNull($user);
    }

    #[Test]
    public function it_can_create_a_record()
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'department_id' => $this->department->id,
        ];

        $user = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertTrue($user->relationLoaded('department'));
    }

    #[Test]
    public function it_can_update_a_record()
    {
        $updateData = [
            'name' => 'John Updated',
        ];

        $updatedUser = $this->userService->update($this->user->id, $updateData);

        $this->assertNotNull($updatedUser);
        $this->assertEquals('John Updated', $updatedUser->name);
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
    public function it_can_delete_a_record()
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


    #[Test]
    public function it_can_search_records_by_text()
    {
        // Create another user
        User::create([
            'name' => 'Jane Smith',
            'department_id' => $this->department->id,
        ]);

        $johnUsers = $this->userService->findAll(['search' => 'john']);
        $janeUsers = $this->userService->findAll(['search' => 'jane']);

        $this->assertCount(1, $johnUsers);
        $this->assertCount(1, $janeUsers);
        $this->assertEquals('John Doe', $johnUsers->first()->name);
        $this->assertEquals('Jane Smith', $janeUsers->first()->name);
    }

    #[Test]
    public function it_can_sort_records()
    {
        // Create another user
        User::create([
            'name' => 'Alice Smith',
            'department_id' => $this->department->id,
        ]);

        $usersAsc = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'asc']);
        $usersDesc = $this->userService->findAll(['sort_by' => 'name', 'sort_direction' => 'desc']);

        $this->assertEquals('Alice Smith', $usersAsc->first()->name);
        $this->assertEquals('John Doe', $usersDesc->first()->name);
    }

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

    #[Test]
    public function it_can_perform_bulk_create()
    {
        $usersData = [
            [
                'name' => 'User 1',
                'department_id' => $this->department->id,
            ],
            [
                'name' => 'User 2',
                'department_id' => $this->department->id,
            ],
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
}
