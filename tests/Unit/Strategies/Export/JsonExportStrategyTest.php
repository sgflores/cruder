<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Export;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Tests\UnitTestCase;

class JsonExportStrategyTest extends UnitTestCase
{
    protected JsonExportStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new JsonExportStrategy();
    }

    public function test_key_returns_json(): void
    {
        $this->assertEquals('json', JsonExportStrategy::key());
    }

    public function test_export_empty_collection(): void
    {
        $data = collect([]);
        $result = $this->strategy->export($data);
        
        $this->assertEquals('[]', $result);
    }

    public function test_export_with_pretty_print(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $result = $this->strategy->export($data);

        $this->assertStringContainsString('    "name"', $result);
        $this->assertStringContainsString('    "email"', $result);
        $this->assertStringContainsString('"John Doe"', $result);
    }

    public function test_export_without_pretty_print(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $options = ['pretty_print' => false];
        $result = $this->strategy->export($data, $options);

        $this->assertStringNotContainsString('    ', $result);
        $this->assertStringContainsString('"name":"John Doe"', $result);
    }

    public function test_export_with_specific_columns(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
        ]);

        $options = ['columns' => ['name', 'email']];
        $result = $this->strategy->export($data, $options);

        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('name', $decoded[0]);
        $this->assertArrayHasKey('email', $decoded[0]);
        $this->assertArrayNotHasKey('age', $decoded[0]);
    }

    public function test_export_with_json_flags(): void
    {
        $data = collect([
            (object) ['name' => 'José', 'email' => 'josé@example.com']
        ]);

        $options = [
            'pretty_print' => false,
            'json_flags' => JSON_UNESCAPED_UNICODE
        ];
        $result = $this->strategy->export($data, $options);

        $this->assertStringContainsString('José', $result);
        $this->assertStringContainsString('josé@example.com', $result);
    }

    public function test_export_with_multiple_records(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com']
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals('John Doe', $decoded[0]['name']);
        $this->assertEquals('Jane Smith', $decoded[1]['name']);
    }

    public function test_export_with_nested_objects(): void
    {
        $data = collect([
            (object) [
                'name' => 'John Doe',
                'profile' => (object) ['age' => 30, 'city' => 'New York']
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('profile', $decoded[0]);
        $this->assertEquals(30, $decoded[0]['profile']['age']);
    }

    public function test_export_with_special_characters(): void
    {
        $data = collect([
            (object) [
                'name' => 'John "Doe"',
                'email' => 'john@example.com',
                'description' => "Line 1\nLine 2"
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertEquals('John "Doe"', $decoded[0]['name']);
        $this->assertEquals("Line 1\nLine 2", $decoded[0]['description']);
    }

    public function test_export_with_null_values(): void
    {
        $data = collect([
            (object) [
                'name' => 'John Doe',
                'email' => null,
                'age' => null
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertEquals('John Doe', $decoded[0]['name']);
        $this->assertNull($decoded[0]['email']);
        $this->assertNull($decoded[0]['age']);
    }

    public function test_export_with_boolean_values(): void
    {
        $data = collect([
            (object) [
                'name' => 'John Doe',
                'active' => true,
                'verified' => false
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertTrue($decoded[0]['active']);
        $this->assertFalse($decoded[0]['verified']);
    }

    public function test_export_with_numeric_values(): void
    {
        $data = collect([
            (object) [
                'name' => 'John Doe',
                'age' => 30,
                'price' => 19.99,
                'score' => 0
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertEquals(30, $decoded[0]['age']);
        $this->assertEquals(19.99, $decoded[0]['price']);
        $this->assertEquals(0, $decoded[0]['score']);
    }

    public function test_export_with_array_values(): void
    {
        $data = collect([
            (object) [
                'name' => 'John Doe',
                'tags' => ['admin', 'user', 'moderator']
            ]
        ]);

        $result = $this->strategy->export($data);

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded[0]['tags']);
        $this->assertEquals(['admin', 'user', 'moderator'], $decoded[0]['tags']);
    }
}
