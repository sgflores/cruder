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
     * @param array $filters Array of query options
     * @param string|null $searchTerm Optional search term
     * @param array $config Optional search configuration
     * @return Builder The modified query builder
     */
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder;
}
