<?php

namespace SgFlores\Cruder\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

/**
 * LIKE search strategy implementation.
 *
 * Provides traditional LIKE-based search functionality for both direct
 * and related model columns. This strategy is more compatible across
 * different database systems.
 */
class LikeSearchStrategy implements SearchStrategyInterface
{
    /**
     * Gets the strategy key.
     */
    public static function key(): string
    {
        return 'like';
    }

    /**
     * Applies LIKE search to the query.
     *
     * @param  Builder|QueryBuilder|null  $query  The query builder instance
     * @param  array  $filters  Array of query options
     * @param  array  $config  Optional search configuration
     * @return Builder|QueryBuilder The modified query builder
     */
    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        // LikeSearchStrategy only works with Eloquent builders
        if ($query === null || ! ($query instanceof Builder)) {
            throw new InvalidArgumentException('LikeSearchStrategy requires an Eloquent Builder instance');
        }

        $term = $config['term'] ?? '';

        // Ensure term is a string
        if (is_array($term)) {
            $term = implode(' ', $term);
        }

        if (empty($term)) {
            return $query;
        }

        $directColumns = $config['direct_columns'] ?? [];
        $relatedColumns = $config['related_columns'] ?? [];

        $query->where(function (Builder $subQuery) use ($term, $directColumns, $relatedColumns) {
            // Search direct columns
            foreach ($directColumns as $column) {
                $subQuery->orWhere($column, 'like', '%'.$term.'%');
            }

            // Search related columns
            foreach ($relatedColumns as $column) {
                $relationParts = $this->parseRelationColumn($column);
                if (! empty($relationParts['relation'])) {
                    // Get the main model and relation to access the related model's table
                    $mainModel = $subQuery->getModel();
                    $relation = $mainModel->{$relationParts['relation']}();
                    $relatedModel = $relation->getRelated();
                    $relatedTable = $relatedModel->getTable();
                    $qualifiedColumn = "{$relatedTable}.{$relationParts['column']}";

                    $subQuery->orWhereHas($relationParts['relation'], function (Builder $relationQuery) use ($qualifiedColumn, $term) {
                        $relationQuery->where($qualifiedColumn, 'like', '%'.$term.'%');
                    });
                }
            }
        });

        return $query;
    }

    /**
     * Parses a relation column string to extract relation and column names.
     *
     * @param  string  $columnString  The column string to parse
     * @return array Array with 'relation' and 'column' keys
     */
    private function parseRelationColumn(string $columnString): array
    {
        // Find the last occurrence of either dot or underscore
        $lastDotPos = strrpos($columnString, '.');
        $lastUnderscorePos = strrpos($columnString, '_');

        // Determine which separator comes last
        if ($lastDotPos === false && $lastUnderscorePos === false) {
            return ['relation' => '', 'column' => $columnString];
        }

        if ($lastDotPos === false) {
            $splitPos = $lastUnderscorePos;
        } elseif ($lastUnderscorePos === false) {
            $splitPos = $lastDotPos;
        } else {
            $splitPos = max($lastDotPos, $lastUnderscorePos);
        }

        $relation = substr($columnString, 0, $splitPos);
        $column = substr($columnString, $splitPos + 1);

        return [
            'relation' => $relation,
            'column' => $column,
        ];
    }
}
