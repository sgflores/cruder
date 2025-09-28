<?php

namespace SgFlores\Cruder\Tests\Unit\Services;

use Mockery;
use SgFlores\Cruder\Services\HookService;
use SgFlores\Cruder\Strategies\Hooks\CallableHook;
use SgFlores\Cruder\Tests\UnitTestCase;

class HookServiceTest extends UnitTestCase
{
    protected HookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HookService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_add_hook(): void
    {
        $hook = new CallableHook(function ($data) {
            return $data;
        });
        
        $this->service->addHook('before_create', $hook);
        
        $this->assertTrue($this->service->hasHooks('before_create'));
    }

    public function test_execute_hooks_with_single_hook(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $hook->shouldReceive('execute')
            ->once()
            ->with('test data')
            ->andReturn('modified data');
        
        $this->service->addHook('before_create', $hook);
        
        $result = $this->service->executeHooks('before_create', 'test data');
        
        $this->assertEquals('modified data', $result);
    }

    public function test_execute_hooks_with_multiple_hooks(): void
    {
        $hook1 = Mockery::mock(CallableHook::class);
        $hook2 = Mockery::mock(CallableHook::class);
        
        $hook1->shouldReceive('execute')
            ->once()
            ->with('test data')
            ->andReturn('data after hook1');
        
        $hook2->shouldReceive('execute')
            ->once()
            ->with('data after hook1')
            ->andReturn('data after hook2');
        
        $this->service->addHook('before_create', $hook1);
        $this->service->addHook('before_create', $hook2);
        
        $result = $this->service->executeHooks('before_create', 'test data');
        
        $this->assertEquals('data after hook2', $result);
    }

    public function test_execute_hooks_with_no_hooks(): void
    {
        $result = $this->service->executeHooks('nonexistent_operation', 'test data');
        
        $this->assertEquals('test data', $result);
    }

    public function test_execute_hooks_with_different_operations(): void
    {
        $beforeHook = Mockery::mock(CallableHook::class);
        $afterHook = Mockery::mock(CallableHook::class);
        
        $beforeHook->shouldReceive('execute')
            ->once()
            ->with('test data')
            ->andReturn('before processed');
        
        $afterHook->shouldReceive('execute')
            ->once()
            ->with('test data')
            ->andReturn('after processed');
        
        $this->service->addHook('before_create', $beforeHook);
        $this->service->addHook('after_create', $afterHook);
        
        $beforeResult = $this->service->executeHooks('before_create', 'test data');
        $afterResult = $this->service->executeHooks('after_create', 'test data');
        
        $this->assertEquals('before processed', $beforeResult);
        $this->assertEquals('after processed', $afterResult);
    }

    public function test_get_available_operations(): void
    {
        $this->service->addHook('before_create', new CallableHook(function ($data) { return $data; }));
        $this->service->addHook('after_create', new CallableHook(function ($data) { return $data; }));
        $this->service->addHook('before_update', new CallableHook(function ($data) { return $data; }));
        
        $operations = $this->service->getAvailableOperations();
        
        $this->assertCount(3, $operations);
        $this->assertContains('before_create', $operations);
        $this->assertContains('after_create', $operations);
        $this->assertContains('before_update', $operations);
    }

    public function test_get_hooks_for_operation(): void
    {
        $hook1 = new CallableHook(function ($data) { return $data; });
        $hook2 = new CallableHook(function ($data) { return $data; });
        
        $this->service->addHook('before_create', $hook1);
        $this->service->addHook('before_create', $hook2);
        
        $hooks = $this->service->getHooksForOperation('before_create');
        
        $this->assertCount(2, $hooks);
        $this->assertContains($hook1, $hooks);
        $this->assertContains($hook2, $hooks);
    }

    public function test_get_hooks_for_nonexistent_operation(): void
    {
        $hooks = $this->service->getHooksForOperation('nonexistent_operation');
        
        $this->assertEmpty($hooks);
    }

    public function test_has_hooks(): void
    {
        $this->assertFalse($this->service->hasHooks('before_create'));
        
        $this->service->addHook('before_create', new CallableHook(function ($data) { return $data; }));
        
        $this->assertTrue($this->service->hasHooks('before_create'));
    }

    public function test_execute_hooks_with_hook_exception(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $hook->shouldReceive('execute')
            ->once()
            ->andThrow(new \Exception('Hook error'));
        
        $this->service->addHook('before_create', $hook);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hook error');
        
        $this->service->executeHooks('before_create', 'test data');
    }

    public function test_execute_hooks_with_complex_data(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $complexData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'profile' => [
                'age' => 30,
                'city' => 'New York'
            ]
        ];
        
        $hook->shouldReceive('execute')
            ->once()
            ->with($complexData)
            ->andReturn($complexData);
        
        $this->service->addHook('before_create', $hook);
        
        $result = $this->service->executeHooks('before_create', $complexData);
        
        $this->assertEquals($complexData, $result);
    }

    public function test_execute_hooks_with_null_data(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $hook->shouldReceive('execute')
            ->once()
            ->with(null)
            ->andReturn('processed null');
        
        $this->service->addHook('before_create', $hook);
        
        $result = $this->service->executeHooks('before_create', null);
        
        $this->assertEquals('processed null', $result);
    }

    public function test_execute_hooks_with_collection_data(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $collection = collect(['item1', 'item2', 'item3']);
        
        $hook->shouldReceive('execute')
            ->once()
            ->with($collection)
            ->andReturn($collection);
        
        $this->service->addHook('before_create', $hook);
        
        $result = $this->service->executeHooks('before_create', $collection);
        
        $this->assertEquals($collection, $result);
    }

    public function test_execute_hooks_with_object_data(): void
    {
        $hook = Mockery::mock(CallableHook::class);
        $object = (object) ['name' => 'John Doe', 'email' => 'john@example.com'];
        
        $hook->shouldReceive('execute')
            ->once()
            ->with($object)
            ->andReturn($object);
        
        $this->service->addHook('before_create', $hook);
        
        $result = $this->service->executeHooks('before_create', $object);
        
        $this->assertEquals($object, $result);
    }

    public function test_add_multiple_hooks_same_operation(): void
    {
        $hook1 = new CallableHook(function ($data) { return $data . '1'; });
        $hook2 = new CallableHook(function ($data) { return $data . '2'; });
        $hook3 = new CallableHook(function ($data) { return $data . '3'; });
        
        $this->service->addHook('before_create', $hook1);
        $this->service->addHook('before_create', $hook2);
        $this->service->addHook('before_create', $hook3);
        
        $hooks = $this->service->getHooksForOperation('before_create');
        
        $this->assertCount(3, $hooks);
    }

    public function test_execute_hooks_chain_processing(): void
    {
        $hook1 = new CallableHook(function ($data) { return $data . ' -> hook1'; });
        $hook2 = new CallableHook(function ($data) { return $data . ' -> hook2'; });
        $hook3 = new CallableHook(function ($data) { return $data . ' -> hook3'; });
        
        $this->service->addHook('before_create', $hook1);
        $this->service->addHook('before_create', $hook2);
        $this->service->addHook('before_create', $hook3);
        
        $result = $this->service->executeHooks('before_create', 'start');
        
        $this->assertEquals('start -> hook1 -> hook2 -> hook3', $result);
    }
}
