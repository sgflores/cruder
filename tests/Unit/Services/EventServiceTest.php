<?php

namespace SgFlores\Cruder\Tests\Unit\Services;

use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Tests\UnitTestCase;

class EventServiceTest extends UnitTestCase
{
    protected EventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EventService();
    }

    public function test_fire_event_with_no_listeners(): void
    {
        $this->service->fire('test_event', 'test_data');
        
        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    public function test_fire_event_with_listeners(): void
    {
        $executed = false;
        
        $this->service->listen('test_event', function ($data) use (&$executed) {
            $executed = true;
            $this->assertEquals('test_data', $data);
        });
        
        $this->service->fire('test_event', 'test_data');
        
        $this->assertTrue($executed);
    }

    public function test_fire_event_with_multiple_listeners(): void
    {
        $executionCount = 0;
        
        $this->service->listen('test_event', function ($data) use (&$executionCount) {
            $executionCount++;
            $this->assertEquals('test_data', $data);
        });
        
        $this->service->listen('test_event', function ($data) use (&$executionCount) {
            $executionCount++;
            $this->assertEquals('test_data', $data);
        });
        
        $this->service->fire('test_event', 'test_data');
        
        $this->assertEquals(2, $executionCount);
    }

    public function test_listen_returns_self(): void
    {
        $result = $this->service->listen('test_event', function () {});
        
        $this->assertSame($this->service, $result);
    }

    public function test_forget_event(): void
    {
        $executed = false;
        
        $this->service->listen('test_event', function () use (&$executed) {
            $executed = true;
        });
        
        $this->service->forget('test_event');
        $this->service->fire('test_event');
        
        $this->assertFalse($executed);
    }

    public function test_flush_removes_all_listeners(): void
    {
        $executed1 = false;
        $executed2 = false;
        
        $this->service->listen('event1', function () use (&$executed1) {
            $executed1 = true;
        });
        
        $this->service->listen('event2', function () use (&$executed2) {
            $executed2 = true;
        });
        
        $this->service->flush();
        
        $this->service->fire('event1');
        $this->service->fire('event2');
        
        $this->assertFalse($executed1);
        $this->assertFalse($executed2);
    }

    public function test_get_events(): void
    {
        $this->service->listen('event1', function () {});
        $this->service->listen('event2', function () {});
        
        $events = $this->service->getEvents();
        
        $this->assertIsArray($events);
        $this->assertContains('event1', $events);
        $this->assertContains('event2', $events);
    }

    public function test_has_listeners(): void
    {
        $this->assertFalse($this->service->hasListeners('test_event'));
        
        $this->service->listen('test_event', function () {});
        
        $this->assertTrue($this->service->hasListeners('test_event'));
    }

    public function test_fire_with_null_data(): void
    {
        $receivedData = 'not_null';
        
        $this->service->listen('test_event', function ($data) use (&$receivedData) {
            $receivedData = $data;
        });
        
        $this->service->fire('test_event', null);
        
        $this->assertNull($receivedData);
    }

    public function test_fire_with_array_data(): void
    {
        $receivedData = null;
        
        $this->service->listen('test_event', function ($data) use (&$receivedData) {
            $receivedData = $data;
        });
        
        $testData = ['key' => 'value', 'number' => 123];
        $this->service->fire('test_event', $testData);
        
        $this->assertEquals($testData, $receivedData);
    }
}
