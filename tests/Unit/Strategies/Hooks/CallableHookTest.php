<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Hooks;

use SgFlores\Cruder\Strategies\Hooks\CallableHook;
use SgFlores\Cruder\Tests\UnitTestCase;

class CallableHookTest extends UnitTestCase
{
    public function test_execute_with_closure(): void
    {
        $data = 'test data';
        $expected = 'modified data';
        
        $closure = function ($input) use ($expected) {
            return $expected;
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertEquals($expected, $result);
    }

    public function test_execute_with_function(): void
    {
        $data = 'test data';
        
        $hook = new CallableHook('strtoupper');
        $result = $hook->execute($data);
        
        $this->assertEquals('TEST DATA', $result);
    }

    public function test_execute_with_array_callback(): void
    {
        $data = 'test data';
        
        $hook = new CallableHook([self::class, 'staticCallback']);
        $result = $hook->execute($data);
        
        $this->assertEquals('STATIC TEST DATA', $result);
    }

    public function test_execute_with_static_method(): void
    {
        $data = 'test data';
        
        $hook = new CallableHook([self::class, 'staticCallback']);
        $result = $hook->execute($data);
        
        $this->assertEquals('STATIC TEST DATA', $result);
    }

    public function test_execute_preserves_original_data(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $expected = ['name' => 'John', 'age' => 30, 'modified' => true];
        
        $closure = function ($input) {
            $input['modified'] = true;
            return $input;
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertEquals($expected, $result);
        $this->assertNotEquals($data, $result); // Original should be different
    }

    public function test_execute_with_complex_data(): void
    {
        $data = (object) [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'profile' => [
                'age' => 30,
                'city' => 'New York'
            ]
        ];
        
        $closure = function ($input) {
            $input->name = strtoupper($input->name);
            $input->profile['age'] = $input->profile['age'] + 1;
            return $input;
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertEquals('JOHN DOE', $result->name);
        $this->assertEquals(31, $result->profile['age']);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function test_execute_with_exception(): void
    {
        $data = 'test data';
        
        $closure = function ($input) {
            throw new \Exception('Test exception');
        };
        
        $hook = new CallableHook($closure);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');
        
        $hook->execute($data);
    }

    public function test_execute_with_null_return(): void
    {
        $data = 'test data';
        
        $closure = function ($input) {
            return null;
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertNull($result);
    }

    public function test_execute_with_multiple_parameters(): void
    {
        $data = 'test data';
        
        $closure = function ($input) {
            return $input . ' processed';
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertEquals('test data processed', $result);
    }

    public function test_execute_with_collection(): void
    {
        $data = collect(['item1', 'item2', 'item3']);
        
        $closure = function ($input) {
            return $input->map(function ($item) {
                return strtoupper($item);
            });
        };
        
        $hook = new CallableHook($closure);
        $result = $hook->execute($data);
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEquals(['ITEM1', 'ITEM2', 'ITEM3'], $result->toArray());
    }

    // Helper methods for testing
    public function helperCallback($input = null): string
    {
        return strtoupper($input ?? 'test data');
    }

    public static function staticCallback($input): string
    {
        return 'STATIC ' . strtoupper($input);
    }
}
