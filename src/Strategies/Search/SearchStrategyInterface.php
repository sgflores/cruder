<?php

namespace SgFlores\Cruder\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface for search strategies.
 * 
 * This interface defines the contract for different search implementations,
 * allowing for easy extension and replacement of search behavior.
 */
interface SearchStrategyInterface
{
    /**
     * Applies search logic to the query builder.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param string $term The search term
     * @param array $config Search configuration
     * @return Builder The modified query builder
     */
    public function search(Builder $query, string $term, array $config): Builder;
}
