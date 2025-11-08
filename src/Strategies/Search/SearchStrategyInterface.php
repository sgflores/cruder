<?php

namespace SgFlores\Cruder\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Interface for search strategies.
 *
 * This interface defines the contract for different search implementations,
 * allowing for easy extension and replacement of search behavior.
 * Strategies can work with both Eloquent and Query builders.
 */
interface SearchStrategyInterface
{
    /**
     * Gets the unique key identifier for this strategy.
     *
     * @return string The strategy key
     */
    public static function key(): string;

    /**
     * Applies search logic to the query builder.
     *
     * @param  Builder|QueryBuilder|null  $query  The query builder instance (optional)
     * @param  array  $filters  Array of query options
     * @param  array  $config  Optional search configuration
     * @return Builder|QueryBuilder The modified query builder
     */
    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder;
}
