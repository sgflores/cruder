<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;
use SgFlores\Cruder\Tests\TestCase;

class LikeSearchStrategyTest extends TestCase
{
    protected LikeSearchStrategy $strategy;
    protected $mockQuery;
    protected $mockSubQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new LikeSearchStrategy();
        $this->mockQuery = Mockery::mock(Builder::class);
        $this->mockSubQuery = Mockery::mock(Builder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_with_direct_columns(): void
    {
        $config = [
            'direct_columns' => ['name', 'email'],
            'related_columns' => []
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $this->mockSubQuery->shouldReceive('orWhere')
            ->with('name', 'like', '%test search%')
            ->once();
        $this->mockSubQuery->shouldReceive('orWhere')
            ->with('email', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_related_columns(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department_name']
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $this->mockSubQuery->shouldReceive('orWhereHas')
            ->with('department', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) {
                $callback($this->mockSubQuery);
                return $this->mockSubQuery;
            });

        $this->mockSubQuery->shouldReceive('where')
            ->with('name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_mixed_columns(): void
    {
        $config = [
            'direct_columns' => ['name', 'email'],
            'related_columns' => ['department_name']
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        // Direct columns
        $this->mockSubQuery->shouldReceive('orWhere')
            ->with('name', 'like', '%test search%')
            ->once();
        $this->mockSubQuery->shouldReceive('orWhere')
            ->with('email', 'like', '%test search%')
            ->once();

        // Related columns
        $this->mockSubQuery->shouldReceive('orWhereHas')
            ->with('department', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) {
                $callback($this->mockSubQuery);
                return $this->mockSubQuery;
            });

        $this->mockSubQuery->shouldReceive('where')
            ->with('name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_empty_config(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => []
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_underscore_notation(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department_name']
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $this->mockSubQuery->shouldReceive('orWhereHas')
            ->with('department', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) {
                $callback($this->mockSubQuery);
                return $this->mockSubQuery;
            });

        $this->mockSubQuery->shouldReceive('where')
            ->with('name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_dot_notation(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department.name']
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $this->mockSubQuery->shouldReceive('orWhereHas')
            ->with('department', Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($relation, $callback) {
                $callback($this->mockSubQuery);
                return $this->mockSubQuery;
            });

        $this->mockSubQuery->shouldReceive('where')
            ->with('name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    public function test_search_with_special_characters(): void
    {
        $config = [
            'direct_columns' => ['name'],
            'related_columns' => []
        ];

        $searchTerm = 'test%_search';
        $filters = ['search' => $searchTerm];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);
                return $this->mockQuery;
            });

        $this->mockSubQuery->shouldReceive('orWhere')
            ->with('name', 'like', '%' . $searchTerm . '%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, null, $config);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }
}
