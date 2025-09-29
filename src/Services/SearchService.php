<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

/**
 * Search service for managing different search strategies.
 * 
 * This service follows the Strategy pattern to provide flexible search
 * functionality. It can be easily extended with new search strategies
 * without modifying existing code.
 */
class SearchService
{
    /**
     * Registered search strategies.
     * 
     * @var array<string, SearchStrategyInterface>
     */
    private array $strategies = [];

    /**
     * Adds a search strategy.
     * 
     * @param string $name The strategy name
     * @param SearchStrategyInterface $strategy The strategy implementation
     * @return void
     */
    public function addStrategy(string $name, SearchStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    /**
     * Applies search using the specified strategy.
     * 
     * Uses the configured search strategy to modify the query builder
     * with appropriate search conditions (LIKE, full-text, etc.).
     * 
     * @param Builder $query The Eloquent query builder instance
     * @param array $filters Array of query options
     * @param string|null $searchTerm Optional search term
     * @param array $config Search configuration
     * @return Builder The modified query builder
     */
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        // Get strategy name from config, default to 'like'
        $strategyName = $config['type'] ?? 'like';
        $strategy = $this->strategies[$strategyName] ?? null;
        
        // Throw exception if strategy not found
        if (!$strategy) {
            throw new \InvalidArgumentException("Search strategy '{$strategyName}' not found");
        }
        
        // Delegate to the strategy to modify the query
        return $strategy->search($query, $filters, $searchTerm, $config);
    }

    /**
     * Gets all registered strategy names.
     * 
     * @return array Array of strategy names
     */
    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Checks if a strategy is registered.
     * 
     * @param string $name The strategy name
     * @return bool True if the strategy exists
     */
    public function hasStrategy(string $name): bool
    {
        return isset($this->strategies[$name]);
    }
}
