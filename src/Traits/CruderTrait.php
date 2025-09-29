<?php

namespace SgFlores\Cruder\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CruderTrait
{
    /**
     * Binds query parameters to the SQL string for debugging purposes.
     * 
     * Converts parameterized SQL queries into readable format by replacing
     * placeholders (?) with actual parameter values for logging/debugging.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @return string The SQL query with parameter values bound
     */
    public function getQueryWithBindings(Builder $query): string
    {
        // Get the parameter bindings and raw SQL
        $bindings = $query->getBindings();
        $sql = $query->toSql();

        // Replace each ? placeholder with the actual parameter value
        return preg_replace_callback('/\?/', function ($match) use (&$bindings, $query) {
            $value = array_shift($bindings);
            // Properly quote the value for SQL display
            return $query->getConnection()->getPdo()->quote($value);
        }, $sql);
    }
}