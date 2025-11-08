<?php

namespace SgFlores\Cruder\Strategies\Export;

use Illuminate\Support\Collection;

/**
 * JSON export strategy implementation.
 *
 * Provides JSON export functionality with pretty printing and custom formatting options.
 * Supports filtering of columns and custom JSON encoding options.
 */
class JsonExportStrategy implements ExportStrategyInterface
{
    /**
     * Gets the strategy key.
     */
    public static function key(): string
    {
        return 'json';
    }

    /**
     * Exports data to JSON format.
     *
     * @param  Collection  $data  The data to export
     * @param  array  $options  Export options
     * @return string JSON formatted data
     */
    public function export(Collection $data, array $options = []): string
    {
        $columns = $options['columns'] ?? [];
        $prettyPrint = $options['pretty_print'] ?? true;
        $flags = $options['json_flags'] ?? 0;

        // Filter columns if specified
        if (! empty($columns)) {
            $data = $data->map(function ($item) use ($columns) {
                $itemArray = $this->convertToArray($item);

                return array_intersect_key($itemArray, array_flip($columns));
            });
        }

        // Convert to array
        $dataArray = $data->toArray();

        // Apply JSON flags
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($dataArray, $flags);
    }

    /**
     * Converts a row object to an array.
     *
     * @param  mixed  $row  The row object
     * @return array The row data as array
     */
    private function convertToArray($row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if (method_exists($row, 'toArray')) {
            return $row->toArray();
        }

        if (is_object($row)) {
            return (array) $row;
        }

        return [];
    }
}
