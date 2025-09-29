<?php

namespace SgFlores\Cruder\Examples;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

/**
 * Custom Search Strategy Example
 * 
 * Demonstrates how to create a custom search strategy for advanced search functionality.
 * Shows fuzzy matching, weighted results, and complex search logic.
 */
class CustomSearchStrategy implements SearchStrategyInterface
{
    /**
     * Apply custom search logic to the query.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options
     * @param string|null $searchTerm Optional search term
     * @param array $config Optional search configuration
     * @return Builder The modified query builder
     */
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        // Use searchTerm parameter if provided, otherwise extract from filters
        $term = $searchTerm ?? $filters['search'] ?? '';
        
        if (empty($term)) {
            return $query;
        }
        
        // Get search configuration
        $columns = $config['direct_columns'] ?? [];
        $relatedColumns = $config['related_columns'] ?? [];
        $fuzzyMatch = $config['fuzzy_match'] ?? false;
        $weightedSearch = $config['weighted_search'] ?? false;
        
        // Clean and prepare search term
        $searchTerms = $this->prepareSearchTerms($term);
        
        if (empty($searchTerms)) {
            return $query;
        }
        
        // Apply search logic
        $query->where(function ($q) use ($searchTerms, $columns, $relatedColumns, $fuzzyMatch, $weightedSearch) {
            foreach ($searchTerms as $searchTerm) {
                $this->applySearchTerm($q, $searchTerm, $columns, $relatedColumns, $fuzzyMatch, $weightedSearch);
            }
        });
        
        // Add ordering for weighted results
        if ($weightedSearch) {
            $this->addWeightedOrdering($query, $term, $columns, $relatedColumns);
        }
        
        return $query;
    }
    
    /**
     * Prepare search terms by splitting and cleaning.
     * 
     * @param string $term
     * @return array
     */
    protected function prepareSearchTerms(string $term): array
    {
        // Remove extra whitespace and split by spaces
        $terms = array_filter(explode(' ', trim($term)));
        
        // Remove common stop words
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $terms = array_diff($terms, $stopWords);
        
        // Clean each term
        return array_map(function ($term) {
            return trim(preg_replace('/[^\w\s-]/', '', $term));
        }, $terms);
    }
    
    /**
     * Apply search term to query.
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param array $columns
     * @param array $relatedColumns
     * @param bool $fuzzyMatch
     * @param bool $weightedSearch
     * @return void
     */
    protected function applySearchTerm(Builder $query, string $searchTerm, array $columns, array $relatedColumns, bool $fuzzyMatch, bool $weightedSearch): void
    {
        // Search in direct columns
        if (!empty($columns)) {
            $query->orWhere(function ($q) use ($searchTerm, $columns, $fuzzyMatch) {
                foreach ($columns as $column) {
                    if ($fuzzyMatch) {
                        // Use SOUNDEX for fuzzy matching
                        $q->orWhereRaw("SOUNDEX({$column}) = SOUNDEX(?)", [$searchTerm])
                          ->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                    }
                }
            });
        }
        
        // Search in related columns
        if (!empty($relatedColumns)) {
            foreach ($relatedColumns as $relatedColumn) {
                $this->searchInRelatedColumn($query, $searchTerm, $relatedColumn, $fuzzyMatch);
            }
        }
    }
    
    /**
     * Search in a related column.
     * 
     * @param Builder $query
     * @param string $searchTerm
     * @param string $relatedColumn
     * @param bool $fuzzyMatch
     * @return void
     */
    protected function searchInRelatedColumn(Builder $query, string $searchTerm, string $relatedColumn, bool $fuzzyMatch): void
    {
        // Parse relation and column
        $parts = explode('.', $relatedColumn);
        if (count($parts) !== 2) {
            return;
        }
        
        [$relation, $column] = $parts;
        
        $query->orWhereHas($relation, function ($q) use ($searchTerm, $column, $fuzzyMatch) {
            if ($fuzzyMatch) {
                $q->whereRaw("SOUNDEX({$column}) = SOUNDEX(?)", [$searchTerm])
                  ->orWhere($column, 'LIKE', "%{$searchTerm}%");
            } else {
                $q->where($column, 'LIKE', "%{$searchTerm}%");
            }
        });
    }
    
    /**
     * Add weighted ordering to search results.
     * 
     * @param Builder $query
     * @param string $term
     * @param array $columns
     * @param array $relatedColumns
     * @return void
     */
    protected function addWeightedOrdering(Builder $query, string $term, array $columns, array $relatedColumns): void
    {
        $weightCases = [];
        
        // Add weights for exact matches
        foreach ($columns as $index => $column) {
            $weight = 10 - $index; // Higher weight for earlier columns
            $weightCases[] = "WHEN {$column} = ? THEN {$weight}";
        }
        
        // Add weights for LIKE matches
        foreach ($columns as $index => $column) {
            $weight = 5 - $index; // Lower weight for LIKE matches
            $weightCases[] = "WHEN {$column} LIKE ? THEN {$weight}";
        }
        
        if (!empty($weightCases)) {
            $weightSql = 'CASE ' . implode(' ', $weightCases) . ' ELSE 0 END';
            $query->selectRaw("*, ({$weightSql}) as search_weight", array_merge(
                array_fill(0, count($columns), $term), // Exact match values
                array_map(fn($col) => "%{$term}%", $columns) // LIKE match values
            ))->orderBy('search_weight', 'desc');
        }
    }
}
