<?php

namespace SgFlores\Cruder\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CruderTrait
{
    /**
     * Binds query parameters to the SQL string for debugging purposes.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @return string The SQL query with parameter values bound
     */
    public function getQueryWithBindings(Builder $query): string
    {
        $bindings = $query->getBindings();
        $sql = $query->toSql();

        return preg_replace_callback('/\?/', function ($match) use (&$bindings, $query) {
            $value = array_shift($bindings);
            return $query->getConnection()->getPdo()->quote($value);
        }, $sql);
    }
}