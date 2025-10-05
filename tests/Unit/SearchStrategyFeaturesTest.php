<?php

namespace SgFlores\Cruder\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
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





    public function test_strategy_key_validation(): void
    {
        $strategy = new LikeSearchStrategy();
        
        // Test that the key method returns the expected value
        $this->assertEquals('like', $strategy::key());
        
        // Test that the strategy can be added with its key
        $this->searchService->addStrategy($strategy::key(), $strategy);
        $this->assertTrue($this->searchService->hasStrategy($strategy::key()));
    }

    public function test_strategies_parameter_accepts_array(): void
    {
        $strategy = new LikeSearchStrategy();
        $this->searchService->addStrategy($strategy::key(), $strategy);
        
        $filters = ['strategies' => ['like']];
        
        // This should not throw an exception
        $result = $this->searchService->search($strategy::key(), $this->mockQuery, $filters, []);
        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_strategies_parameter_accepts_comma_separated_string(): void
    {
        $strategy = new LikeSearchStrategy();
        $this->searchService->addStrategy($strategy::key(), $strategy);
        
        $filters = ['strategies' => 'like'];
        
        // This should not throw an exception
        $result = $this->searchService->search($strategy::key(), $this->mockQuery, $filters, []);
        $this->assertInstanceOf(Builder::class, $result);
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

    public function test_throws_error_when_calling_findAll_with_strategies_without_search_service(): void
    {
        // Create a service without SearchService
        $serviceWithoutSearch = new class(new User()) extends BaseReaderService {
            public function __construct(User $user)
            {
                parent::__construct($user); // No SearchService provided
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
        $this->expectExceptionMessage('SearchService is required for strategy execution');

        $serviceWithoutSearch->findAll([
            'strategies' => ['like']
        ]);
    }

    public function test_does_not_throw_error_when_calling_findAll_without_strategies(): void
    {
        // Create a service without SearchService
        $serviceWithoutSearch = new class(new User()) extends BaseReaderService {
            public function __construct(User $user)
            {
                parent::__construct($user); // No SearchService provided
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

        // This should work without throwing an exception since no strategies are provided
        $result = $serviceWithoutSearch->findAll([]);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
