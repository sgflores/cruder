<?php

namespace SgFlores\Cruder\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Mockery;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\UnitTestCase;

class ExportServiceTest extends UnitTestCase
{
    protected ExportService $service;
    protected Collection $testData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExportService();
        $this->testData = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com']
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_add_strategy(): void
    {
        $strategy = new CsvExportStrategy();
        
        $this->service->addStrategy('csv', $strategy);
        
        $this->assertTrue($this->service->hasFormat('csv'));
    }

    public function test_add_strategy_by_key(): void
    {
        $strategy = new CsvExportStrategy();
        
        $this->service->addStrategyByKey($strategy);
        
        $this->assertTrue($this->service->hasFormat('csv'));
    }

    public function test_add_strategy_by_key_uses_strategy_key(): void
    {
        $csvStrategy = new CsvExportStrategy();
        $jsonStrategy = new JsonExportStrategy();
        
        $this->service->addStrategyByKey($csvStrategy);
        $this->service->addStrategyByKey($jsonStrategy);
        
        $this->assertTrue($this->service->hasFormat('csv'));
        $this->assertTrue($this->service->hasFormat('json'));
        
        $formats = $this->service->getAvailableFormats();
        $this->assertContains('csv', $formats);
        $this->assertContains('json', $formats);
    }

    public function test_export_with_existing_strategy(): void
    {
        $strategy = Mockery::mock(CsvExportStrategy::class);
        $strategy->shouldReceive('export')
            ->once()
            ->with($this->testData, ['columns' => ['name', 'email']])
            ->andReturn('name,email\nJohn Doe,john@example.com');
        
        $this->service->addStrategy('csv', $strategy);
        
        $result = $this->service->export('csv', $this->testData, ['columns' => ['name', 'email']]);
        
        $this->assertEquals('name,email\nJohn Doe,john@example.com', $result);
    }

    public function test_export_with_nonexistent_strategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Export format 'nonexistent' not supported");
        
        $this->service->export('nonexistent', $this->testData);
    }

    public function test_export_with_nonexistent_strategy_shows_available_formats(): void
    {
        $this->service->addStrategy('csv', new CsvExportStrategy());
        $this->service->addStrategy('json', new JsonExportStrategy());
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Export format 'nonexistent' not supported. Available formats: csv, json");
        
        $this->service->export('nonexistent', $this->testData);
    }

    public function test_get_available_formats(): void
    {
        $this->service->addStrategy('csv', new CsvExportStrategy());
        $this->service->addStrategy('json', new JsonExportStrategy());
        
        $formats = $this->service->getAvailableFormats();
        
        $this->assertCount(2, $formats);
        $this->assertContains('csv', $formats);
        $this->assertContains('json', $formats);
    }

    public function test_has_format(): void
    {
        $this->assertFalse($this->service->hasFormat('csv'));
        
        $this->service->addStrategy('csv', new CsvExportStrategy());
        
        $this->assertTrue($this->service->hasFormat('csv'));
    }

    public function test_export_with_empty_data(): void
    {
        $strategy = Mockery::mock(CsvExportStrategy::class);
        $strategy->shouldReceive('export')
            ->once()
            ->with(Mockery::on(function($data) {
                return $data instanceof \Illuminate\Support\Collection && $data->isEmpty();
            }), [])
            ->andReturn('');
        
        $this->service->addStrategy('csv', $strategy);
        
        $result = $this->service->export('csv', collect([]), []);
        
        $this->assertEquals('', $result);
    }

    public function test_export_with_complex_options(): void
    {
        $strategy = Mockery::mock(JsonExportStrategy::class);
        $options = [
            'columns' => ['name', 'email'],
            'pretty_print' => true,
            'json_flags' => JSON_UNESCAPED_UNICODE
        ];
        
        $strategy->shouldReceive('export')
            ->once()
            ->with($this->testData, $options)
            ->andReturn('{"name":"John Doe","email":"john@example.com"}');
        
        $this->service->addStrategy('json', $strategy);
        
        $result = $this->service->export('json', $this->testData, $options);
        
        $this->assertEquals('{"name":"John Doe","email":"john@example.com"}', $result);
    }

    public function test_export_with_multiple_strategies(): void
    {
        $csvStrategy = Mockery::mock(CsvExportStrategy::class);
        $jsonStrategy = Mockery::mock(JsonExportStrategy::class);
        
        $this->service->addStrategy('csv', $csvStrategy);
        $this->service->addStrategy('json', $jsonStrategy);
        
        // Test CSV export
        $csvStrategy->shouldReceive('export')
            ->once()
            ->with($this->testData, [])
            ->andReturn('name,email\nJohn Doe,john@example.com');
        
        $result = $this->service->export('csv', $this->testData);
        $this->assertEquals('name,email\nJohn Doe,john@example.com', $result);
        
        // Test JSON export
        $jsonStrategy->shouldReceive('export')
            ->once()
            ->with($this->testData, [])
            ->andReturn('{"name":"John Doe","email":"john@example.com"}');
        
        $result = $this->service->export('json', $this->testData);
        $this->assertEquals('{"name":"John Doe","email":"john@example.com"}', $result);
    }

    public function test_replace_strategy(): void
    {
        $firstStrategy = Mockery::mock(CsvExportStrategy::class);
        $secondStrategy = Mockery::mock(CsvExportStrategy::class);
        
        $this->service->addStrategy('csv', $firstStrategy);
        $this->service->addStrategy('csv', $secondStrategy); // Replace
        
        $secondStrategy->shouldReceive('export')
            ->once()
            ->with($this->testData, [])
            ->andReturn('replaced strategy output');
        
        $result = $this->service->export('csv', $this->testData);
        
        $this->assertEquals('replaced strategy output', $result);
    }

    public function test_export_with_strategy_exception(): void
    {
        $strategy = Mockery::mock(CsvExportStrategy::class);
        $strategy->shouldReceive('export')
            ->once()
            ->andThrow(new \Exception('Strategy error'));
        
        $this->service->addStrategy('csv', $strategy);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Strategy error');
        
        $this->service->export('csv', $this->testData);
    }

    public function test_export_with_empty_options(): void
    {
        $strategy = Mockery::mock(CsvExportStrategy::class);
        $strategy->shouldReceive('export')
            ->once()
            ->with($this->testData, [])
            ->andReturn('exported data');
        
        $this->service->addStrategy('csv', $strategy);
        
        $result = $this->service->export('csv', $this->testData);
        
        $this->assertEquals('exported data', $result);
    }

    public function test_export_with_empty_collection(): void
    {
        $strategy = Mockery::mock(CsvExportStrategy::class);
        $strategy->shouldReceive('export')
            ->once()
            ->with(Mockery::on(function($data) {
                return $data instanceof \Illuminate\Support\Collection && $data->isEmpty();
            }), [])
            ->andReturn('empty data exported');
        
        $this->service->addStrategy('csv', $strategy);
        
        $result = $this->service->export('csv', collect([]), []);
        
        $this->assertEquals('empty data exported', $result);
    }

    public function test_throws_error_when_calling_export_without_export_service()
    {
        // Create a service without ExportService
        $serviceWithoutExport = new class(new User()) extends BaseReaderService {
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

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ExportService is required for export functionality');

        $serviceWithoutExport->export('csv', []);
    }
}
