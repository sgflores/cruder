<?php

namespace SgFlores\Cruder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class EventServiceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected TestUserService $userService;

    protected User $user;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = new TestUserService(new User);

        // Create department first
        $this->department = Department::create([
            'name' => 'Test Department',
            'code' => 'TEST',
            'description' => 'Test Department for testing',
        ]);

        // Create test data
        $this->user = User::create([
            'name' => 'John Doe',
            'department_id' => $this->department->id,
        ]);
    }

    #[Test]
    public function it_works_with_event_service_provided()
    {
        // Test that CRUD operations work when EventService is provided
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $userData = [
            'name' => 'Jane Doe',
            'department_id' => $this->department->id,
        ];

        $user = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals(1, $user->created_by);
    }

    #[Test]
    public function it_works_without_event_service_provided()
    {
        // Create a service without EventService
        $serviceWithoutEvents = new class(new User) extends BaseCrudService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No EventService provided
            }

            public function getDirectFilterableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getDirectSortableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getSingleRecordRelations(): array
            {
                return [];
            }

            public function getCollectionRelations(): array
            {
                return [];
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
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $userData = [
            'name' => 'Jane Doe',
            'department_id' => $this->department->id,
        ];

        // This should work without throwing an exception
        $user = $serviceWithoutEvents->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals(1, $user->created_by);
    }

    #[Test]
    public function it_throws_exception_when_adding_event_listener_without_event_service()
    {
        // Create a service without EventService
        $serviceWithoutEvents = new class(new User) extends BaseCrudService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No EventService provided
            }

            public function getDirectFilterableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getDirectSortableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getSingleRecordRelations(): array
            {
                return [];
            }

            public function getCollectionRelations(): array
            {
                return [];
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
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EventService is required for event functionality');

        $serviceWithoutEvents->addEventListener(EventService::BEFORE_CREATE, function ($data) {
            return $data;
        });
    }

    #[Test]
    public function it_returns_null_event_service_when_not_provided()
    {
        // Create a service without EventService
        $serviceWithoutEvents = new class(new User) extends BaseCrudService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No EventService provided
            }

            public function getDirectFilterableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getDirectSortableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getSingleRecordRelations(): array
            {
                return [];
            }

            public function getCollectionRelations(): array
            {
                return [];
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
        };

        $this->assertNull($serviceWithoutEvents->getEventService());
    }

    #[Test]
    public function it_returns_event_service_when_provided()
    {
        $this->assertInstanceOf(EventService::class, $this->userService->getEventService());
    }

    #[Test]
    public function it_handles_bulk_operations_without_event_service()
    {
        // Create a service without EventService
        $serviceWithoutEvents = new class(new User) extends BaseCrudService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No EventService provided
            }

            public function getDirectFilterableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getDirectSortableColumns(): array
            {
                return ['id', 'name', 'department_id'];
            }

            public function getSingleRecordRelations(): array
            {
                return [];
            }

            public function getCollectionRelations(): array
            {
                return [];
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
        };

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        $usersData = [
            ['name' => 'User 1', 'department_id' => $this->department->id],
            ['name' => 'User 2', 'department_id' => $this->department->id],
        ];

        // This should work without throwing an exception
        $success = $serviceWithoutEvents->bulkCreate($usersData);

        $this->assertTrue($success);
        $this->assertDatabaseCount('test_users', 3); // 1 existing + 2 new
    }
}
