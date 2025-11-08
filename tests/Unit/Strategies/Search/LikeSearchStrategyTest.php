<?php

namespace SgFlores\Cruder\Tests\Unit\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SgFlores\Cruder\Strategies\Search\LikeSearchStrategy;

class LikeSearchStrategyTest extends OrchestraTestCase
{
    protected LikeSearchStrategy $strategy;

    protected $mockQuery;

    protected $mockSubQuery;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new LikeSearchStrategy;
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
            'related_columns' => [],
            'term' => 'test search',
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

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_key_returns_correct_value(): void
    {
        $this->assertEquals('like', $this->strategy::key());
    }

    public function test_search_with_related_columns(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department_name'],
            'term' => 'test search',
        ];

        $filters = ['search' => 'test search'];

        // Mock the model chain
        $mockModel = Mockery::mock();
        $mockRelation = Mockery::mock();
        $mockRelatedModel = Mockery::mock();

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) use ($mockModel, $mockRelation, $mockRelatedModel) {
                // Mock getModel() to return the model
                $this->mockSubQuery->shouldReceive('getModel')
                    ->once()
                    ->andReturn($mockModel);

                // Mock model's relation method
                $mockModel->shouldReceive('department')
                    ->once()
                    ->andReturn($mockRelation);

                // Mock relation's getRelated()
                $mockRelation->shouldReceive('getRelated')
                    ->once()
                    ->andReturn($mockRelatedModel);

                // Mock related model's getTable()
                $mockRelatedModel->shouldReceive('getTable')
                    ->once()
                    ->andReturn('departments');

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
            ->with('departments.name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_mixed_columns(): void
    {
        $config = [
            'direct_columns' => ['name', 'email'],
            'related_columns' => ['department_name'],
            'term' => 'test search',
        ];

        $filters = ['search' => 'test search'];

        // Mock the model chain
        $mockModel = Mockery::mock();
        $mockRelation = Mockery::mock();
        $mockRelatedModel = Mockery::mock();

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) use ($mockModel, $mockRelation, $mockRelatedModel) {
                // Mock getModel() to return the model
                $this->mockSubQuery->shouldReceive('getModel')
                    ->once()
                    ->andReturn($mockModel);

                // Mock model's relation method
                $mockModel->shouldReceive('department')
                    ->once()
                    ->andReturn($mockRelation);

                // Mock relation's getRelated()
                $mockRelation->shouldReceive('getRelated')
                    ->once()
                    ->andReturn($mockRelatedModel);

                // Mock related model's getTable()
                $mockRelatedModel->shouldReceive('getTable')
                    ->once()
                    ->andReturn('departments');

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
            ->with('departments.name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_empty_config(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => [],
            'term' => 'test search',
        ];

        $filters = ['search' => 'test search'];

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                $callback($this->mockSubQuery);

                return $this->mockQuery;
            });

        // No additional expectations since there are no columns to search

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_underscore_notation(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department_name'],
            'term' => 'test search',
        ];

        $filters = ['search' => 'test search'];

        // Mock the model chain
        $mockModel = Mockery::mock();
        $mockRelation = Mockery::mock();
        $mockRelatedModel = Mockery::mock();

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) use ($mockModel, $mockRelation, $mockRelatedModel) {
                // Mock getModel() to return the model
                $this->mockSubQuery->shouldReceive('getModel')
                    ->once()
                    ->andReturn($mockModel);

                // Mock model's relation method
                $mockModel->shouldReceive('department')
                    ->once()
                    ->andReturn($mockRelation);

                // Mock relation's getRelated()
                $mockRelation->shouldReceive('getRelated')
                    ->once()
                    ->andReturn($mockRelatedModel);

                // Mock related model's getTable()
                $mockRelatedModel->shouldReceive('getTable')
                    ->once()
                    ->andReturn('departments');

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
            ->with('departments.name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_dot_notation(): void
    {
        $config = [
            'direct_columns' => [],
            'related_columns' => ['department.name'],
            'term' => 'test search',
        ];

        $filters = ['search' => 'test search'];

        // Mock the model chain
        $mockModel = Mockery::mock();
        $mockRelation = Mockery::mock();
        $mockRelatedModel = Mockery::mock();

        $this->mockQuery->shouldReceive('where')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) use ($mockModel, $mockRelation, $mockRelatedModel) {
                // Mock getModel() to return the model
                $this->mockSubQuery->shouldReceive('getModel')
                    ->once()
                    ->andReturn($mockModel);

                // Mock model's relation method
                $mockModel->shouldReceive('department')
                    ->once()
                    ->andReturn($mockRelation);

                // Mock relation's getRelated()
                $mockRelation->shouldReceive('getRelated')
                    ->once()
                    ->andReturn($mockRelatedModel);

                // Mock related model's getTable()
                $mockRelatedModel->shouldReceive('getTable')
                    ->once()
                    ->andReturn('departments');

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
            ->with('departments.name', 'like', '%test search%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }

    public function test_search_with_special_characters(): void
    {
        $config = [
            'direct_columns' => ['name'],
            'related_columns' => [],
            'term' => 'test%_search',
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
            ->with('name', 'like', '%'.$searchTerm.'%')
            ->once();

        $result = $this->strategy->search($this->mockQuery, $filters, $config);

        $this->assertInstanceOf(Builder::class, $result);
    }
}
