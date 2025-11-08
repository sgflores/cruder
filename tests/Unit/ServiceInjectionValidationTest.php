<?php

namespace SgFlores\Cruder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\TestCase;

class ServiceInjectionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        // Create department for user creation
        $this->department = Department::create([
            'name' => 'Test Department',
            'code' => 'TEST',
            'description' => 'Test Department for testing',
        ]);
    }

    #[Test]
    public function it_throws_error_when_calling_add_event_listener_without_event_service()
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('EventService is required for event functionality');

        $serviceWithoutEvents->addEventListener(EventService::BEFORE_CREATE, function ($data) {
            return $data;
        });
    }

    #[Test]
    public function it_throws_error_when_calling_find_all_with_strategies_without_search_service()
    {
        // Create a service without SearchService
        $serviceWithoutSearch = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No SearchService provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SearchService is required for strategy execution');

        $serviceWithoutSearch->findAll([
            'strategies' => ['like'],
        ]);
    }

    #[Test]
    public function it_does_not_throw_error_when_calling_find_all_without_strategies_and_without_search_service()
    {
        // Create a service without SearchService
        $serviceWithoutSearch = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No SearchService provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        // Create test data
        User::create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // This should work without throwing an exception
        $result = $serviceWithoutSearch->findAll([]);

        $this->assertCount(1, $result);
        $this->assertEquals('Test User', $result->first()->name);
    }

    #[Test]
    public function it_throws_error_when_calling_export_without_export_service()
    {
        // Create a service without ExportService
        $serviceWithoutExport = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No ExportService provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ExportService is required for export functionality');

        $serviceWithoutExport->export('csv', []);
    }

    #[Test]
    public function it_returns_null_when_getting_event_service_that_was_not_injected()
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
    public function it_returns_null_when_getting_search_service_that_was_not_injected()
    {
        // Create a service without SearchService
        $serviceWithoutSearch = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No SearchService provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        $this->assertNull($serviceWithoutSearch->getSearchService());
    }

    #[Test]
    public function it_returns_null_when_getting_export_service_that_was_not_injected()
    {
        // Create a service without ExportService
        $serviceWithoutExport = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No ExportService provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        $this->assertNull($serviceWithoutExport->getExportService());
    }

    #[Test]
    public function it_returns_null_when_getting_query_logger_that_was_not_injected()
    {
        // Create a service without QueryLogger
        $serviceWithoutQueryLogger = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No QueryLogger provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        $this->assertNull($serviceWithoutQueryLogger->getQueryLogger());
    }

    #[Test]
    public function it_works_when_calling_find_all_without_query_logger()
    {
        // Create a service without QueryLogger
        $serviceWithoutQueryLogger = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No QueryLogger provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        // Create test data
        User::create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // This should work without throwing an exception
        $result = $serviceWithoutQueryLogger->findAll([]);

        $this->assertCount(1, $result);
        $this->assertEquals('Test User', $result->first()->name);
    }

    #[Test]
    public function it_works_when_calling_count_without_query_logger()
    {
        // Create a service without QueryLogger
        $serviceWithoutQueryLogger = new class(new User) extends BaseReaderService
        {
            public function __construct(User $user)
            {
                parent::__construct($user); // No QueryLogger provided
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

            public function getSearchParam(): string
            {
                return 'search';
            }

            public function getStrategiesParam(): string
            {
                return 'strategies';
            }
        };

        // Create test data
        User::create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        // This should work without throwing an exception
        $count = $serviceWithoutQueryLogger->count([]);

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_works_when_calling_crud_operations_without_event_service()
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

        // Mock Auth
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);

        // Create operation should work without EventService
        $user = $serviceWithoutEvents->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);

        // Update operation should work without EventService
        $updatedUser = $serviceWithoutEvents->update($user->id, [
            'name' => 'Updated User',
        ]);

        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals('Updated User', $updatedUser->name);

        // Delete operation should work without EventService
        $deleted = $serviceWithoutEvents->delete($user->id);

        $this->assertTrue($deleted);
    }
}
