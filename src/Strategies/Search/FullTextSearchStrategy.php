<?php

namespace SgFlores\Cruder\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Full-text search strategy implementation.
 * 
 * Provides MySQL full-text search functionality with boolean mode support.
 * This strategy is optimized for large datasets and provides relevance scoring.
 */
class FullTextSearchStrategy implements SearchStrategyInterface
{
    /**
     * Applies full-text search to the query.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param string $term The search term
     * @param array $config Search configuration
     * @return Builder The modified query builder
     */
    public function search(Builder $query, string $term, array $config): Builder
    {
        if (!$config['enabled'] || empty($config['columns'])) {
            return $query;
        }
        
        $columns = implode(',', $config['columns']);
        $query->whereRaw("MATCH({$columns}) AGAINST(? IN BOOLEAN MODE)", [$term]);
        
        return $query;
    }
}
