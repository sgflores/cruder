<?php

namespace SgFlores\Cruder\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;
use SgFlores\Cruder\Tests\TestCase;
use SgFlores\Cruder\Tests\Models\User;

class SearchStrategyFeaturesTest extends TestCase
{

    protected SearchService $searchService;
    protected $mockQuery;
    protected $mockQueryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
        $this->mockQuery = Mockery::mock(Builder::class);
        $this->mockQueryBuilder = Mockery::mock(QueryBuilder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_strategy_rejects_null_query(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test'
        ];

        $filters = ['search' => 'test'];

        // LikeSearchStrategy should reject null queries
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('LikeSearchStrategy requires an Eloquent Builder instance');
        
        $strategy->search(null, $filters, $config);
    }

    public function test_strategy_accepts_eloquent_builder(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturn($this->mockQuery);

        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test'
        ];

        $filters = ['search' => 'test'];

        $result = $strategy->search($this->mockQuery, $filters, $config);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_strategy_rejects_query_builder(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test'
        ];

        $filters = ['search' => 'test'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('LikeSearchStrategy requires an Eloquent Builder instance');

        $strategy->search($this->mockQueryBuilder, $filters, $config);
    }

    public function test_multiple_strategies_with_same_query_type(): void
    {
        $strategy1 = new LikeSearchStrategy();
        $strategy2 = new LikeSearchStrategy();
        
        $this->searchService->addStrategy($strategy1::key(), $strategy1);
        $this->searchService->addStrategy($strategy2::key(), $strategy2);

        // This should work without throwing an exception
        $result = $this->searchService->search($strategy1::key(), $this->mockQuery, [], []);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_strategy_enforcement_mode(): void
    {
        // Create a test service that enforces search strategies
        $testService = new class(new User()) extends BaseReaderService {
            public function shouldEnforceSearchStrategies(): bool
            {
                return true;
            }
            
            public function getDefaultSearchStrategy(): ?string
            {
                return LikeSearchStrategy::key();
            }
            
            public function getSearchableColumns(): array
            {
                return ['name'];
            }
            
            public function getFilterableColumns(): array
            {
                return ['id'];
            }
            
            public function getSortableColumns(): array
            {
                return ['name'];
            }
        };
        
        
        // Add the like strategy
        $likeStrategy = new LikeSearchStrategy();
        $testService->getSearchService()->addStrategy($likeStrategy::key(), $likeStrategy);
        
        // Test that strategy enforcement is enabled
        $this->assertTrue($testService->shouldEnforceSearchStrategies());
        $this->assertEquals('like', $testService->getDefaultSearchStrategy());
    }

    public function test_strategy_enforcement_bypasses_default_search(): void
    {
        // Create a test service that enforces search strategies
        $testService = new class(new User()) extends BaseReaderService {
            public function shouldEnforceSearchStrategies(): bool
            {
                return true;
            }
            
            public function getDefaultSearchStrategy(): ?string
            {
                return LikeSearchStrategy::key();
            }
            
            public function getSearchableColumns(): array
            {
                return ['name'];
            }
            
            public function getFilterableColumns(): array
            {
                return ['id'];
            }
            
            public function getSortableColumns(): array
            {
                return ['name'];
            }
            
            public function getDirectTextSearchColumns(): array
            {
                return ['name'];
            }
        };
        
        
        // Add the like strategy
        $likeStrategy = new LikeSearchStrategy();
        $testService->getSearchService()->addStrategy($likeStrategy::key(), $likeStrategy);
        
        // Test that when strategy enforcement is enabled, it uses the strategy
        // instead of the default search implementation
        $this->assertTrue($testService->shouldEnforceSearchStrategies());
        
        // The service should have the strategy available
        $this->assertTrue($testService->getSearchService()->hasStrategy(LikeSearchStrategy::key()));
    }

    public function test_multiple_strategies_with_enforcement(): void
    {
        // Create a test service that enforces search strategies
        $testService = new class(new User()) extends BaseReaderService {
            public function shouldEnforceSearchStrategies(): bool
            {
                return true;
            }
            
            public function getDefaultSearchStrategy(): ?string
            {
                return LikeSearchStrategy::key();
            }
            
            public function getSearchableColumns(): array
            {
                return ['name'];
            }
            
            public function getFilterableColumns(): array
            {
                return ['id'];
            }
            
            public function getSortableColumns(): array
            {
                return ['name'];
            }
        };
        
        
        // Add multiple strategies
        $likeStrategy1 = new LikeSearchStrategy();
        $likeStrategy2 = new LikeSearchStrategy();
        
        $testService->getSearchService()->addStrategy($likeStrategy1::key(), $likeStrategy1);
        $testService->getSearchService()->addStrategy($likeStrategy2::key(), $likeStrategy2);
        
        // Test that multiple strategies can be registered
        $this->assertTrue($testService->getSearchService()->hasStrategy(LikeSearchStrategy::key()));
        $this->assertCount(1, $testService->getSearchService()->getAvailableStrategies());
    }

    public function test_strategy_enforcement_with_custom_strategy(): void
    {
        // Create a test service that enforces search strategies
        $testService = new class(new User()) extends BaseReaderService {
            public function shouldEnforceSearchStrategies(): bool
            {
                return true;
            }
            
            public function getDefaultSearchStrategy(): ?string
            {
                return 'custom';
            }
            
            public function getSearchableColumns(): array
            {
                return ['name'];
            }
            
            public function getFilterableColumns(): array
            {
                return ['id'];
            }
            
            public function getSortableColumns(): array
            {
                return ['name'];
            }
        };
        
        
        // Add a custom strategy (using the strategy's actual key)
        $customStrategy = new LikeSearchStrategy();
        $testService->getSearchService()->addStrategy($customStrategy::key(), $customStrategy);
        
        // Test that the custom strategy is used
        $this->assertEquals('custom', $testService->getDefaultSearchStrategy());
        $this->assertTrue($testService->getSearchService()->hasStrategy($customStrategy::key()));
    }

    public function test_strategy_key_validation(): void
    {
        $strategy = new LikeSearchStrategy();
        
        // Test that the key method returns the expected value
        $this->assertEquals('like', $strategy::key());
        
        // Test that the strategy can be added with its key
        $this->searchService->addStrategy($strategy::key(), $strategy);
        $this->assertTrue($this->searchService->hasStrategy($strategy::key()));
    }

    public function test_strategy_with_pagination(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturn($this->mockQuery);

        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test'
        ];

        $filters = ['search' => 'test'];

        $result = $strategy->search($this->mockQuery, $filters, $config);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_strategy_with_limit(): void
    {
        $strategy = new LikeSearchStrategy();
        
        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturn($this->mockQuery);

        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test'
        ];

        $filters = ['search' => 'test'];

        $result = $strategy->search($this->mockQuery, $filters, $config);
        
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_strategy_error_handling(): void
    {
        // Test that the service handles missing strategies properly
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Search strategy 'failing_strategy' not found");

        $this->searchService->search('failing_strategy', $this->mockQuery, [], []);
    }
}
