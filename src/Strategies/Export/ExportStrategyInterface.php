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
     * Exports data in the specific format.
     * 
     * @param Collection $data The data to export
     * @param array $options Export options (columns, formatting, etc.)
     * @return string The exported data
     */
    public function export(Collection $data, array $options = []): string;
}
