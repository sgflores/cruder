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
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $this->assertTrue($this->service->hasStrategy($strategy::key()));
    }

    public function test_search_with_existing_strategy(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $result = $this->service->search($strategy::key(), $this->mockQuery, [], []);
        
        // Verify the strategy was called and result returned
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_nonexistent_strategy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Search strategy 'nonexistent' not found");
        
        $this->service->search('nonexistent', $this->mockQuery, [], []);
    }

    public function test_get_available_strategies(): void
    {
        $likeStrategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($likeStrategy::key(), $likeStrategy);
        
        $strategies = $this->service->getAvailableStrategies();
        
        $this->assertCount(1, $strategies);
        $this->assertContains($likeStrategy::key(), $strategies);
    }

    public function test_has_strategy(): void
    {
        $this->assertFalse($this->service->hasStrategy(LikeSearchStrategy::key()));
        
        $this->service->addStrategy(LikeSearchStrategy::key(), new LikeSearchStrategy());
        
        $this->assertTrue($this->service->hasStrategy(LikeSearchStrategy::key()));
    }

    public function test_search_with_default_strategy(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $result = $this->service->search($strategy::key(), $this->mockQuery, [], []);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_multiple_strategies(): void
    {
        $likeStrategy1 = new LikeSearchStrategy();
        $likeStrategy2 = new LikeSearchStrategy();
        
        $this->service->addStrategy($likeStrategy1::key(), $likeStrategy1);
        $this->service->addStrategy($likeStrategy2::key(), $likeStrategy2);
        
        // Test like strategy
        $result1 = $this->service->search($likeStrategy1::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result1);
        
        // Test second like strategy
        $result2 = $this->service->search($likeStrategy2::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result2);
    }

    public function test_search_with_complex_config(): void
    {
        $strategy = new LikeSearchStrategy();
        
        // Set up mock expectations for the strategy
        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturn($this->mockQuery);
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $config = [
            'term' => 'test search',
            'enabled' => true,
            'direct_columns' => ['name', 'email'],
            'related_columns' => ['department_name']
        ];
        
        $result = $this->service->search($strategy::key(), $this->mockQuery, [], $config);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_empty_term(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $result = $this->service->search($strategy::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_special_characters(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($strategy::key(), $strategy);
        
        $result = $this->service->search($strategy::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_replace_strategy(): void
    {
        $firstStrategy = new LikeSearchStrategy();
        $secondStrategy = new LikeSearchStrategy();
        
        $this->service->addStrategy($firstStrategy::key(), $firstStrategy);
        $this->service->addStrategy($secondStrategy::key(), $secondStrategy); // Replace
        
        $result = $this->service->search($secondStrategy::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result);
    }
}
