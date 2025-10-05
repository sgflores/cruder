<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Export;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Tests\UnitTestCase;

class CsvExportStrategyTest extends UnitTestCase
{
    protected CsvExportStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new CsvExportStrategy();
    }

    public function test_key_returns_csv(): void
    {
        $this->assertEquals('csv', CsvExportStrategy::key());
    }

    public function test_export_empty_collection(): void
    {
        $data = collect([]);
        $result = $this->strategy->export($data);
        
        $this->assertEquals('', $result);
    }

    public function test_export_with_headers(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com']
        ]);

        $result = $this->strategy->export($data);

        $this->assertStringContainsString('name,email', $result);
        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString('jane@example.com', $result);
    }

    public function test_export_without_headers(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $options = ['include_headers' => false];
        $result = $this->strategy->export($data, $options);

        $this->assertStringNotContainsString('name,email', $result);
        $this->assertStringContainsString('John Doe', $result);
    }

    public function test_export_with_specific_columns(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30]
        ]);

        $options = ['columns' => ['name', 'email']];
        $result = $this->strategy->export($data, $options);

        $this->assertStringContainsString('name,email', $result);
        $this->assertStringNotContainsString('age', $result);
    }

    public function test_export_escapes_special_characters(): void
    {
        $data = collect([
            (object) ['name' => 'John "Doe"', 'email' => 'john@example.com,test']
        ]);

        $result = $this->strategy->export($data);

        $this->assertStringContainsString('"John ""Doe"""', $result);
        $this->assertStringContainsString('"john@example.com,test"', $result);
    }

    public function test_export_handles_newlines(): void
    {
        $data = collect([
            (object) ['name' => "John\nDoe", 'email' => 'john@example.com']
        ]);

        $result = $this->strategy->export($data);

        $this->assertStringContainsString('"John', $result);
        $this->assertStringContainsString('Doe"', $result);
    }

    public function test_export_handles_empty_values(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => '', 'age' => null]
        ]);

        $result = $this->strategy->export($data);

        $this->assertStringContainsString('John Doe', $result);
        $this->assertStringContainsString(',,', $result); // Empty values
    }

    public function test_export_handles_missing_columns(): void
    {
        $data = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com']
        ]);

        $options = ['columns' => ['name', 'email', 'phone']];
        $result = $this->strategy->export($data, $options);

        $this->assertStringContainsString('name,email,phone', $result);
        $this->assertStringContainsString('John Doe,john@example.com,', $result);
    }

    public function test_export_with_complex_data(): void
    {
        $data = collect([
            (object) [
                'name' => 'John "Doe"',
                'email' => 'john@example.com',
                'description' => 'Line 1, Line 2',
                'price' => 19.99
            ]
        ]);

        $result = $this->strategy->export($data);

        $lines = explode("\n", $result);
        $this->assertCount(2, $lines); // Header + data
        
        $this->assertStringContainsString('"John ""Doe"""', $result);
        $this->assertStringContainsString('"Line 1, Line 2"', $result);
    }
}
