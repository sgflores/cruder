<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
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
     * @param  string  $name  The strategy name
     * @param  SearchStrategyInterface  $strategy  The strategy implementation
     *
     * @throws InvalidArgumentException If strategy key doesn't match the provided name
     */
    public function addStrategy(string $name, SearchStrategyInterface $strategy): void
    {
        // Validate that the strategy key matches the provided name
        if ($strategy::key() !== $name) {
            throw new InvalidArgumentException(
                "Strategy key mismatch. Expected '{$name}', but strategy returns '{$strategy::key()}'"
            );
        }

        $this->strategies[$name] = $strategy;
    }

    /**
     * Applies search using the specified strategy.
     *
     * Uses the configured search strategy to modify the query builder
     * with appropriate search conditions (LIKE, full-text, etc.).
     *
     * @param  string  $strategyName  The strategy name to use
     * @param  Builder|QueryBuilder|null  $query  The query builder instance
     * @param  array  $filters  Array of query options
     * @param  array  $config  Search configuration
     * @return Builder|QueryBuilder The modified query builder
     *
     * @throws InvalidArgumentException If strategy not found
     */
    public function search(string $strategyName, Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        $strategy = $this->strategies[$strategyName] ?? null;

        // Throw exception if strategy not found
        if (! $strategy) {
            throw new InvalidArgumentException("Search strategy '{$strategyName}' not found");
        }

        // Delegate to the strategy to modify the query
        return $strategy->search($query, $filters, $config);
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
     * @param  string  $name  The strategy name
     * @return bool True if the strategy exists
     */
    public function hasStrategy(string $name): bool
    {
        return isset($this->strategies[$name]);
    }

    /**
     * Gets a strategy instance by name.
     *
     * @param  string  $name  The strategy name
     * @return SearchStrategyInterface|null The strategy instance or null if not found
     */
    public function getStrategy(string $name): ?SearchStrategyInterface
    {
        return $this->strategies[$name] ?? null;
    }
}
