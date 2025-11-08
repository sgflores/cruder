<?php

namespace SgFlores\Cruder\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class QueryLoggingTest extends TestCase
{
    protected TestUserService $userService;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = new TestUserService(new User);
        $this->department = Department::factory()->create();

        // Clear any existing query log
        DB::flushQueryLog();
    }

    public function test_query_logging_can_be_enabled_via_config(): void
    {
        // Enable query logging via config
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);

        // Mock the log facade
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) && isset($data['message']) && $data['message'] === 'CRUD Query';
            }))
            ->atLeast()->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Perform an operation that should trigger logging
        $this->userService->findAll();
    }

    public function test_query_logging_can_be_disabled_via_config(): void
    {
        // Disable query logging via config
        config(['cruder.query_logging.enabled' => false]);

        // Mock the log facade - should not be called
        Log::shouldReceive('channel')->never();
        Log::shouldReceive('debug')->never();

        // Perform an operation that should not trigger logging
        $this->userService->findAll();
    }

    public function test_specific_operations_can_be_disabled(): void
    {
        // Test that find operations can be disabled
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => false]);
        config(['cruder.query_logging.operations.find' => false]);

        // Mock the log facade - should not be called for find
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')->never();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        $this->userService->findAll();
    }

    public function test_specific_operations_can_be_enabled(): void
    {
        // Test that create operations can be enabled
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => false]);
        config(['cruder.query_logging.operations.create' => true]);

        // Mock the log facade for create (should be logged)
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) && isset($data['message']) && $data['message'] === 'CRUD Query';
            }))
            ->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        $this->userService->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);
    }

    public function test_slow_queries_are_logged_with_warning_level(): void
    {
        // Enable query logging with slow query detection
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);
        config(['cruder.query_logging.slow_query_threshold' => 0.001]); // Very low threshold for testing
        config(['cruder.performance.slow_query_log_level' => 'warning']);

        // Mock the log facade
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('warning')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) && isset($data['message']) && $data['message'] === 'CRUD Query';
            }))
            ->atLeast()->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Perform an operation that should be considered slow
        $this->userService->findAll();
    }

    public function test_query_logging_includes_execution_time(): void
    {
        // Enable query logging
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);
        config(['cruder.query_logging.include_execution_time' => true]);

        // Mock the log facade
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) &&
                       isset($data['message']) && $data['message'] === 'CRUD Query' &&
                       isset($data['execution_time_ms']) && is_numeric($data['execution_time_ms']);
            }))
            ->atLeast()->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Perform an operation
        $this->userService->findAll();
    }

    public function test_query_logging_includes_bindings(): void
    {
        // Enable query logging
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);
        config(['cruder.query_logging.include_bindings' => true]);

        // Mock the log facade
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) &&
                       isset($data['message']) && $data['message'] === 'CRUD Query' &&
                       isset($data['bindings']) && is_array($data['bindings']);
            }))
            ->atLeast()->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Perform an operation
        $this->userService->findAll();
    }

    public function test_query_logging_includes_context_data(): void
    {
        // Enable query logging
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);

        // Mock the log facade
        $mockChannel = \Mockery::mock();
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) &&
                       isset($data['message']) && $data['message'] === 'CRUD Query' &&
                       isset($data['operation']) &&
                       isset($data['sql']) &&
                       isset($data['table']) &&
                       isset($data['context']);
            }))
            ->atLeast()->once();

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Perform an operation
        $this->userService->findAll();
    }

    public function test_query_logger_can_be_accessed(): void
    {
        $queryLogger = $this->userService->getQueryLogger();

        $this->assertInstanceOf(\SgFlores\Cruder\Services\QueryLogger::class, $queryLogger);
    }

    public function test_query_logging_works_for_all_crud_operations(): void
    {
        // Enable query logging
        config(['cruder.query_logging.enabled' => true]);
        config(['cruder.query_logging.log_all_operations' => true]);

        // Mock the log facade
        $mockChannel = \Mockery::mock();

        // Expect 4 calls total (4 operations × 1 call each with combined data)
        $mockChannel->shouldReceive('debug')
            ->with(\Mockery::on(function ($data) {
                return is_array($data) && isset($data['message']) && $data['message'] === 'CRUD Query';
            }))
            ->atLeast()->times(4);

        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturn($mockChannel);

        // Test all CRUD operations
        $this->userService->findAll();

        $user = $this->userService->create([
            'name' => 'Test User',
            'department_id' => $this->department->id,
        ]);

        $this->userService->update($user->id, ['name' => 'Updated User']);

        $this->userService->delete($user->id);
    }
}
