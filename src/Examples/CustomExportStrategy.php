<?php

namespace SgFlores\Cruder\Examples;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;

/**
 * Custom Export Strategy Example
 * 
 * Demonstrates how to create a custom export strategy for specific data formats.
 * Shows XML export with custom formatting and data transformation.
 */
class CustomExportStrategy implements ExportStrategyInterface
{
    /**
     * Export data in XML format.
     * 
     * @param Collection $data The data to export
     * @param array $options Export options
     * @return string The exported data
     */
    public function export(Collection $data, array $options = []): string
    {
        // Get export configuration
        $rootElement = $options['root_element'] ?? 'items';
        $itemElement = $options['item_element'] ?? 'item';
        $columns = $options['columns'] ?? [];
        $includeHeaders = $options['include_headers'] ?? true;
        $dateFormat = $options['date_format'] ?? 'Y-m-d H:i:s';
        
        // Start building XML
        $xml = new \SimpleXMLElement("<?xml version='1.0' encoding='UTF-8'?><{$rootElement}></{$rootElement}>");
        
        // Add metadata if headers are included
        if ($includeHeaders) {
            $metadata = $xml->addChild('metadata');
            $metadata->addChild('export_date', now()->format($dateFormat));
            $metadata->addChild('total_records', $data->count());
            $metadata->addChild('exported_by', 'System'); // Example: current user name
        }
        
        // Add data items
        $items = $xml->addChild('items');
        
        foreach ($data as $item) {
            $this->addItemToXml($items, $item, $itemElement, $columns, $dateFormat);
        }
        
        // Format XML output
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        return $dom->saveXML();
    }
    
    /**
     * Add a single item to XML.
     * 
     * @param \SimpleXMLElement $parent
     * @param mixed $item
     * @param string $itemElement
     * @param array $columns
     * @param string $dateFormat
     * @return void
     */
    protected function addItemToXml(\SimpleXMLElement $parent, $item, string $itemElement, array $columns, string $dateFormat): void
    {
        $itemNode = $parent->addChild($itemElement);
        
        // Convert item to array if it's a model
        $itemData = $this->convertItemToArray($item);
        
        // Filter columns if specified
        if (!empty($columns)) {
            $itemData = array_intersect_key($itemData, array_flip($columns));
        }
        
        // Add each field to XML
        foreach ($itemData as $key => $value) {
            $this->addFieldToXml($itemNode, $key, $value, $dateFormat);
        }
    }
    
    /**
     * Add a field to XML with proper formatting.
     * 
     * @param \SimpleXMLElement $itemNode
     * @param string $key
     * @param mixed $value
     * @param string $dateFormat
     * @return void
     */
    protected function addFieldToXml(\SimpleXMLElement $itemNode, string $key, $value, string $dateFormat): void
    {
        // Clean field name for XML
        $fieldName = $this->cleanFieldName($key);
        
        // Format value based on type
        $formattedValue = $this->formatValue($value, $dateFormat);
        
        // Add field to XML
        if (is_array($formattedValue)) {
            // Handle nested arrays/objects
            $this->addNestedDataToXml($itemNode, $fieldName, $formattedValue, $dateFormat);
        } else {
            $itemNode->addChild($fieldName, htmlspecialchars($formattedValue, ENT_XML1, 'UTF-8'));
        }
    }
    
    /**
     * Add nested data to XML.
     * 
     * @param \SimpleXMLElement $parent
     * @param string $fieldName
     * @param array $data
     * @param string $dateFormat
     * @return void
     */
    protected function addNestedDataToXml(\SimpleXMLElement $parent, string $fieldName, array $data, string $dateFormat): void
    {
        $nestedNode = $parent->addChild($fieldName);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->addNestedDataToXml($nestedNode, $key, $value, $dateFormat);
            } else {
                $nestedNode->addChild($key, htmlspecialchars($this->formatValue($value, $dateFormat), ENT_XML1, 'UTF-8'));
            }
        }
    }
    
    /**
     * Convert item to array.
     * 
     * @param mixed $item
     * @return array
     */
    protected function convertItemToArray($item): array
    {
        if (is_array($item)) {
            return $item;
        }
        
        if (method_exists($item, 'toArray')) {
            return $item->toArray();
        }
        
        if (is_object($item)) {
            return (array) $item;
        }
        
        return [$item];
    }
    
    /**
     * Clean field name for XML.
     * 
     * @param string $fieldName
     * @return string
     */
    protected function cleanFieldName(string $fieldName): string
    {
        // Replace invalid XML characters
        $fieldName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fieldName);
        
        // Ensure it doesn't start with a number
        if (preg_match('/^[0-9]/', $fieldName)) {
            $fieldName = 'field_' . $fieldName;
        }
        
        return $fieldName;
    }
    
    /**
     * Format value for XML output.
     * 
     * @param mixed $value
     * @param string $dateFormat
     * @return mixed
     */
    protected function formatValue($value, string $dateFormat)
    {
        if (is_null($value)) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format($dateFormat);
        }
        
        if (is_array($value)) {
            return $value; // Will be handled by nested data method
        }
        
        return (string) $value;
    }
}
