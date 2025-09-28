<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use SgFlores\Cruder\Strategies\Search\FullTextSearchStrategy;
use SgFlores\Cruder\Tests\TestCase;

class FullTextSearchStrategyTest extends TestCase
{
    protected FullTextSearchStrategy $strategy;
    protected $mockQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new FullTextSearchStrategy();
        $this->mockQuery = Mockery::mock(Builder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_with_enabled_config(): void
    {
        $config = [
            'enabled' => true,
            'columns' => ['name', 'email']
        ];

        $this->mockQuery->shouldReceive('whereRaw')
            ->once()
            ->with('MATCH(name,email) AGAINST(? IN BOOLEAN MODE)', ['test search']);

        $this->strategy->search($this->mockQuery, 'test search', $config);
        
        // Verify the mock was called as expected
        $this->assertTrue(true); // Mock verification happens in tearDown
    }

    public function test_search_with_disabled_config(): void
    {
        $config = [
            'enabled' => false,
            'columns' => ['name', 'email']
        ];

        $this->mockQuery->shouldNotReceive('whereRaw');

        $this->strategy->search($this->mockQuery, 'test search', $config);
        
        // Verify no query modifications were made when disabled
        $this->assertTrue(true); // Mock verification happens in tearDown
    }

    public function test_search_with_empty_columns(): void
    {
        $config = [
            'enabled' => true,
            'columns' => []
        ];

        $this->mockQuery->shouldNotReceive('whereRaw');

        $this->strategy->search($this->mockQuery, 'test search', $config);
        
        // Verify no query modifications were made with empty columns
        $this->assertTrue(true); // Mock verification happens in tearDown
    }

    public function test_search_with_single_column(): void
    {
        $config = [
            'enabled' => true,
            'columns' => ['name']
        ];

        $this->mockQuery->shouldReceive('whereRaw')
            ->once()
            ->with('MATCH(name) AGAINST(? IN BOOLEAN MODE)', ['test search']);

        $this->strategy->search($this->mockQuery, 'test search', $config);
        
        // Verify the mock was called as expected
        $this->assertTrue(true); // Mock verification happens in tearDown
    }

    public function test_search_with_multiple_columns(): void
    {
        $config = [
            'enabled' => true,
            'columns' => ['name', 'email', 'bio']
        ];

        $this->mockQuery->shouldReceive('whereRaw')
            ->once()
            ->with('MATCH(name,email,bio) AGAINST(? IN BOOLEAN MODE)', ['test search']);

        $this->strategy->search($this->mockQuery, 'test search', $config);
        
        // Verify the mock was called as expected
        $this->assertTrue(true); // Mock verification happens in tearDown
    }

    public function test_search_with_special_characters(): void
    {
        $config = [
            'enabled' => true,
            'columns' => ['name', 'email']
        ];

        $searchTerm = 'test +search -exclude "exact phrase"';
        
        $this->mockQuery->shouldReceive('whereRaw')
            ->once()
            ->with('MATCH(name,email) AGAINST(? IN BOOLEAN MODE)', [$searchTerm]);

        $this->strategy->search($this->mockQuery, $searchTerm, $config);
        
        // Verify the mock was called as expected
        $this->assertTrue(true); // Mock verification happens in tearDown
    }
}
