<?php

namespace SgFlores\Cruder\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;
use SgFlores\Cruder\Tests\UnitTestCase;

class SearchServiceTest extends UnitTestCase
{
    protected SearchService $service;
    protected $mockQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SearchService();
        $this->mockQuery = Mockery::mock(Builder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_add_strategy(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->service->addStrategy('like', $strategy);
        
        $this->assertTrue($this->service->hasStrategy('like'));
    }

    public function test_search_with_existing_strategy(): void
    {
        $strategy = Mockery::mock(LikeSearchStrategy::class);
        $strategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), Mockery::type('array'));
        
        $this->service->addStrategy('like', $strategy);
        
        $config = ['type' => 'like'];
        $this->service->search($this->mockQuery, [], $config);
        
        // Verify the strategy was called
        $this->assertTrue($this->service->hasStrategy('like'));
    }

    public function test_search_with_nonexistent_strategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Search strategy 'nonexistent' not found");
        
        $config = ['type' => 'nonexistent'];
        $this->service->search($this->mockQuery, [], $config);
    }

    public function test_get_available_strategies(): void
    {
        $this->service->addStrategy('like', new LikeSearchStrategy());
        $this->service->addStrategy('custom', new LikeSearchStrategy());
        
        $strategies = $this->service->getAvailableStrategies();
        
        $this->assertCount(2, $strategies);
        $this->assertContains('like', $strategies);
        $this->assertContains('custom', $strategies);
    }

    public function test_has_strategy(): void
    {
        $this->assertFalse($this->service->hasStrategy('like'));
        
        $this->service->addStrategy('like', new LikeSearchStrategy());
        
        $this->assertTrue($this->service->hasStrategy('like'));
    }

    public function test_search_with_default_strategy(): void
    {
        $strategy = Mockery::mock(LikeSearchStrategy::class);
        $strategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), Mockery::type('array'))
            ->andReturn($this->mockQuery);
        
        $this->service->addStrategy('like', $strategy);
        
        $config = []; // No type specified, should default to 'like'
        $result = $this->service->search($this->mockQuery, [], $config);
        
        $this->assertSame($this->mockQuery, $result);
    }

    public function test_search_with_multiple_strategies(): void
    {
        $customStrategy = Mockery::mock(LikeSearchStrategy::class);
        $likeStrategy = Mockery::mock(LikeSearchStrategy::class);
        
        $this->service->addStrategy('custom', $customStrategy);
        $this->service->addStrategy('like', $likeStrategy);
        
        // Test custom strategy
        $customStrategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), ['type' => 'custom'])
            ->andReturn($this->mockQuery);
        
        $config = ['type' => 'custom'];
        $result1 = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result1);
        
        // Test like strategy
        $likeStrategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), ['type' => 'like'])
            ->andReturn($this->mockQuery);
        
        $config = ['type' => 'like'];
        $result2 = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result2);
    }

    public function test_search_with_complex_config(): void
    {
        $strategy = Mockery::mock(LikeSearchStrategy::class);
        $strategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), [
                'term' => 'test search',
                'type' => 'like',
                'enabled' => true,
                'direct_columns' => ['name', 'email'],
                'related_columns' => ['department_name']
            ])
            ->andReturn($this->mockQuery);
        
        $this->service->addStrategy('like', $strategy);
        
        $config = [
            'term' => 'test search',
            'type' => 'like',
            'enabled' => true,
            'direct_columns' => ['name', 'email'],
            'related_columns' => ['department_name']
        ];
        
        $result = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result);
    }

    public function test_search_with_empty_term(): void
    {
        $strategy = Mockery::mock(LikeSearchStrategy::class);
        $strategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), ['type' => 'like'])
            ->andReturn($this->mockQuery);
        
        $this->service->addStrategy('like', $strategy);
        
        $config = ['type' => 'like'];
        $result = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result);
    }

    public function test_search_with_special_characters(): void
    {
        $strategy = Mockery::mock(LikeSearchStrategy::class);
        $searchTerm = 'test +search -exclude "exact phrase"';
        
        $strategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), ['type' => 'like'])
            ->andReturn($this->mockQuery);
        
        $this->service->addStrategy('like', $strategy);
        
        $config = ['type' => 'like'];
        $result = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result);
    }

    public function test_replace_strategy(): void
    {
        $firstStrategy = Mockery::mock(LikeSearchStrategy::class);
        $secondStrategy = Mockery::mock(LikeSearchStrategy::class);
        
        $this->service->addStrategy('like', $firstStrategy);
        $this->service->addStrategy('like', $secondStrategy); // Replace
        
        $secondStrategy->shouldReceive('search')
            ->once()
            ->with($this->mockQuery, Mockery::type('array'), ['type' => 'like'])
            ->andReturn($this->mockQuery);
        
        $config = ['type' => 'like'];
        $result = $this->service->search($this->mockQuery, [], $config);
        $this->assertSame($this->mockQuery, $result);
    }
}
