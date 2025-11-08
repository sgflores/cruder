<?php

namespace SgFlores\Cruder\Strategies\Export;

use Illuminate\Support\Collection;

/**
 * Interface for export strategies.
 *
 * This interface defines the contract for different export implementations,
 * allowing for easy extension and addition of new export formats.
 */
interface ExportStrategyInterface
{
    /**
     * Gets the unique key identifier for this strategy.
     *
     * @return string The strategy key
     */
    public static function key(): string;

    /**
     * Exports data in the specific format.
     *
     * @param  Collection  $data  The data to export
     * @param  array  $options  Export options (columns, formatting, etc.)
     * @return string The exported data
     */
    public function export(Collection $data, array $options = []): string;
}
