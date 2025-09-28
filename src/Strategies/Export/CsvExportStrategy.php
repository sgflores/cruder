<?php

namespace SgFlores\Cruder\Strategies\Export;

use Illuminate\Support\Collection;

/**
 * CSV export strategy implementation.
 * 
 * Provides CSV export functionality with proper escaping and formatting.
 * Supports custom column selection and header configuration.
 */
class CsvExportStrategy implements ExportStrategyInterface
{
    /**
     * Exports data to CSV format.
     * 
     * @param Collection $data The data to export
     * @param array $options Export options
     * @return string CSV formatted data
     */
    public function export(Collection $data, array $options = []): string
    {
        if ($data->isEmpty()) {
            return '';
        }
        
        $columns = $options['columns'] ?? [];
        $includeHeaders = $options['include_headers'] ?? true;
        
        $csv = '';
        
        // Add headers if requested
        if ($includeHeaders) {
            $firstRow = $data->first();
            $rowData = $this->convertToArray($firstRow);
            $headers = empty($columns) ? array_keys($rowData) : $columns;
            $csv .= $this->escapeCsvRow($headers) . "\n";
        }
        
        // Add data rows
        foreach ($data as $row) {
            $rowData = $this->convertToArray($row);
            $csvRow = [];
            
            $headers = empty($columns) ? array_keys($rowData) : $columns;
            foreach ($headers as $header) {
                $value = $rowData[$header] ?? '';
                $csvRow[] = $value;
            }
            
            $csv .= $this->escapeCsvRow($csvRow) . "\n";
        }
        
        return rtrim($csv, "\n");
    }

    /**
     * Converts a row object to an array.
     * 
     * @param mixed $row The row object
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

    /**
     * Escapes a CSV row for proper formatting.
     * 
     * @param array $row The row data to escape
     * @return string The escaped CSV row
     */
    private function escapeCsvRow(array $row): string
    {
        $escapedRow = [];
        
        foreach ($row as $value) {
            // Convert to string and escape quotes
            $value = (string) $value;
            $value = str_replace('"', '""', $value);
            
            // Wrap in quotes if contains comma, quote, or newline
            if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                $value = '"' . $value . '"';
            }
            
            $escapedRow[] = $value;
        }
        
        return implode(',', $escapedRow);
    }
}
