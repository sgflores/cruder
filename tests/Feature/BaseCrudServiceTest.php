<?php

namespace SgFlores\Cruder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class BaseCrudServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TestUserService $userService;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Create test data
        $this->department = Department::factory()->create();
        $this->user = User::factory()->create([
            'name' => 'Setup User',
            'department_id' => $this->department->id
        ]);
        
        // Create service instance
        $this->userService = new TestUserService(new User());
        
        // Mock Auth for audit trail tests
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        try {
            Cache::flush();
        } catch (\Exception $e) {
            // Ignore cache flush errors in tests with mocked cache
        }
        parent::tearDown();
    }

    // ========================================================================
    // --- CRUD Operations Tests ---
    // ========================================================================

    public function test_find_all_returns_collection(): void
    {
        $result = $this->userService->findAll();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($this->user->id, $result->first()->id);
    }

    public function test_find_all_with_pagination(): void
    {
        // Create more test_users
        User::factory()->count(5)->create(['department_id' => $this->department->id]);
        
        $result = $this->userService->findAll(['paginate' => 3]);
        
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(6, $result->total());
    }

    public function test_find_all_with_limit(): void
    {
        // Create more test_users
        User::factory()->count(5)->create(['department_id' => $this->department->id]);
        
        $result = $this->userService->findAll(['limit' => 3]);
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_find_by_id_returns_model(): void
    {
        $result = $this->userService->findById($this->user->id);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($this->user->id, $result->id);
    }

    public function test_find_by_id_returns_null_for_nonexistent(): void
    {
        $result = $this->userService->findById(999);
        
        $this->assertNull($result);
    }

    public function test_find_by_id_with_relations(): void
    {
        $result = $this->userService->findById($this->user->id, ['department']);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->relationLoaded('department'));
    }

    public function test_create_returns_model(): void
    {
        $data = [
            'name' => 'John Doe',
            'department_id' => $this->department->id
        ];
        
        $result = $this->userService->create($data);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertDatabaseHas('test_users', [
            'name' => 'John Doe'
        ]);
    }

    public function test_create_with_audit_trail(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'department_id' => $this->department->id
        ];
        
        $result = $this->userService->create($data);
        
        $this->assertEquals(1, $result->created_by);
        $this->assertDatabaseHas('test_users', [
            'name' => 'Jane Doe',
            'created_by' => 1
        ]);
    }

    public function test_create_with_validation_rules(): void
    {
        $this->expectException(ValidationException::class);
        
        $data = [
            'name' => '', // Invalid: required
            'department_id' => 999 // Invalid: doesn't exist
        ];
        
        $this->userService->create($data);
    }

    public function test_update_returns_model(): void
    {
        $data = ['name' => 'Updated Name'];
        
        $result = $this->userService->update($this->user->id, $data);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Updated Name', $result->name);
        $this->assertDatabaseHas('test_users', [
            'id' => $this->user->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_update_returns_null_for_nonexistent(): void
    {
        $result = $this->userService->update(999, ['name' => 'Updated Name']);
        
        $this->assertNull($result);
    }

    public function test_update_with_audit_trail(): void
    {
        $data = ['name' => 'Updated Name'];
        
        $result = $this->userService->update($this->user->id, $data);
        
        $this->assertEquals(1, $result->updated_by);
        $this->assertDatabaseHas('test_users', [
            'id' => $this->user->id,
            'updated_by' => 1
        ]);
    }

    public function test_delete_returns_boolean(): void
    {
        $result = $this->userService->delete($this->user->id);
        
        $this->assertTrue($result);
        $this->assertSoftDeleted('test_users', ['id' => $this->user->id]);
    }

    public function test_delete_returns_false_for_nonexistent(): void
    {
        $result = $this->userService->delete(999);
        
        $this->assertFalse($result);
    }

    public function test_force_delete(): void
    {
        $result = $this->userService->delete($this->user->id, true);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('test_users', ['id' => $this->user->id]);
    }

    // ========================================================================
    // --- Search and Filtering Tests ---
    // ========================================================================

    public function test_search_by_text(): void
    {
        User::factory()->create([
            'name' => 'John Smith',
            'department_id' => $this->department->id
        ]);
        
        $result = $this->userService->findAll(['search' => 'John']);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John Smith', $result->first()->name);
    }




    public function test_filter_by_department_id(): void
    {
        $newDepartment = Department::factory()->create();
        User::factory()->create(['department_id' => $newDepartment->id]);
        
        $result = $this->userService->findAll(['department_id' => $newDepartment->id]);
        
        $this->assertCount(1, $result);
        $this->assertEquals($newDepartment->id, $result->first()->department_id);
    }


    // ========================================================================
    // --- Sorting Tests ---
    // ========================================================================

    public function test_sort_by_name_asc(): void
    {
        $alice = User::factory()->create([
            'name' => 'Alice',
            'department_id' => $this->department->id
        ]);
        $bob = User::factory()->create([
            'name' => 'Bob',
            'department_id' => $this->department->id
        ]);
        
        $result = $this->userService->findAll([
            'sort_by' => 'name', 
            'sort_direction' => 'asc',
            'id' => [$alice->id, $bob->id]
        ]);
        
        $this->assertEquals('Alice', $result->first()->name);
        $this->assertEquals('Bob', $result->last()->name);
    }

    public function test_sort_by_name_desc(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Alice',
            'department_id' => $this->department->id
        ]);
        $user2 = User::factory()->create([
            'name' => 'Bob',
            'department_id' => $this->department->id
        ]);
        
        $result = $this->userService->findAll([
            'sort_by' => 'name', 
            'sort_direction' => 'desc',
            'id' => [$user1->id, $user2->id]
        ]);
        
        $this->assertEquals('Bob', $result->first()->name);
        $this->assertEquals('Alice', $result->last()->name);
    }

    public function test_sort_by_created_at(): void
    {
        $oldUser = User::factory()->create([
            'created_at' => now()->subDays(2),
            'department_id' => $this->department->id
        ]);
        $newUser = User::factory()->create([
            'created_at' => now()->subDay(),
            'department_id' => $this->department->id
        ]);
        
        $result = $this->userService->findAll([
            'sort_by' => 'created_at', 
            'sort_direction' => 'desc',
            'id' => [$oldUser->id, $newUser->id]
        ]);
        
        $this->assertEquals($newUser->id, $result->first()->id);
        $this->assertEquals($oldUser->id, $result->last()->id);
    }

    // ========================================================================
    // --- Count Tests ---
    // ========================================================================

    public function test_count_returns_integer(): void
    {
        User::factory()->count(3)->create(['department_id' => $this->department->id]);
        
        $result = $this->userService->count();
        
        $this->assertIsInt($result);
        $this->assertEquals(4, $result); // 1 original + 3 new
    }


    // ========================================================================
    // --- Mass Operations Tests ---
    // ========================================================================

    public function test_bulk_create(): void
    {
        $data = [
            [
                'name' => 'User 1',
                'department_id' => $this->department->id
            ],
            [
                'name' => 'User 2',
                'department_id' => $this->department->id
            ]
        ];
        
        $result = $this->userService->bulkCreate($data);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('test_users', ['name' => 'User 1']);
        $this->assertDatabaseHas('test_users', ['name' => 'User 2']);
    }




    // ========================================================================
    // --- Export Tests ---
    // ========================================================================

    public function test_export_csv(): void
    {
        User::factory()->count(2)->create(['department_id' => $this->department->id]);
        
        $result = $this->userService->export('csv', [], ['name']);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('name', $result);
        $this->assertStringContainsString($this->user->name, $result);
    }

    public function test_export_json(): void
    {
        User::factory()->count(2)->create(['department_id' => $this->department->id]);
        
        $result = $this->userService->export('json', [], ['name']);
        
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data); // 1 original + 2 new
    }


    // ========================================================================
    // --- Search Suggestions Tests ---
    // ========================================================================

    public function test_get_search_suggestions(): void
    {
        User::factory()->create([
            'name' => 'John Smith',
            'department_id' => $this->department->id
        ]);
        User::factory()->create([
            'name' => 'John Doe',
            'department_id' => $this->department->id
        ]);
        
        $result = $this->userService->getSearchSuggestions('John');
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertGreaterThan(0, $result->count());
        $this->assertTrue($result->contains('John Smith'));
    }

    // ========================================================================
    // --- Hooks Tests ---
    // ========================================================================

    public function test_hooks_are_executed(): void
    {
        $hookExecuted = false;
        
        $this->userService->addHook('after_create', function ($user) use (&$hookExecuted) {
            $hookExecuted = true;
            return $user;
        });
        
        $data = [
            'name' => 'Test User',
            'department_id' => $this->department->id
        ];
        
        $this->userService->create($data);
        
        $this->assertTrue($hookExecuted);
    }

    // ========================================================================
    // --- Cache Tests ---
    // ========================================================================

    public function test_cache_is_used(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([$this->user]));
        
        Cache::shouldReceive('flush')
            ->andReturn(true);
        
        $result = $this->userService->findAll();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_cache_is_cleared_on_create(): void
    {
        Cache::shouldReceive('tags')
            ->once()
            ->andReturnSelf();
        Cache::shouldReceive('flush')
            ->atLeast()
            ->once();
        
        $data = [
            'name' => 'Test User',
            'department_id' => $this->department->id
        ];
        
        $this->userService->create($data);
    }
}
