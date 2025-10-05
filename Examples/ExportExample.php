<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Export Example Class
 * 
 * Demonstrates how to use the BaseReaderService for data export operations.
 * Shows both default export strategies (JSON, CSV) and custom export strategies (XML).
 * 
 * This example extends BaseReaderService to provide export functionality
 * for any Eloquent model using the built-in filtering and export capabilities.
 */
class ExportExample extends BaseReaderService
{
    /**
     * ExportExample constructor.
     * 
     * @param Model $model The Eloquent model to export data from
     * @param SearchService $searchService Search service
     * @param ExportService $exportService Export service
     * @param EventService $eventService Event service
     * @param QueryLogger $queryLogger Query logger
     */
    public function __construct(
        Model $model,
        SearchService $searchService,
        ExportService $exportService,
        EventService $eventService,
        QueryLogger $queryLogger
    ) {
        parent::__construct($model, $searchService, $exportService, $eventService, $queryLogger);
        
        // Configure services after construction
        $this->configureServices();
    }

    /**
     * Configures custom export strategies.
     * 
     * Overrides the parent method to add custom export strategies
     * including the default JSON and CSV strategies plus custom XML strategy.
     * 
     * @return void
     */
    protected function configureServices(): void
    {
        parent::configureServices();
        
        // Register default export strategies
        $this->exportService->addStrategy('json', new JsonExportStrategy());
        $this->exportService->addStrategy('csv', new CsvExportStrategy());
        
        // Register custom XML export strategy
        $this->exportService->addStrategy('xml', new XmlExportStrategy());
    }

    /**
     * Exports data in JSON format with default settings.
     * 
     * @param array $filters Optional filters to apply
     * @param array $columns Optional columns to include
     * @param array $options Optional export options
     * @return string JSON formatted data
     */
    public function exportToJson(array $filters = [], array $columns = [], array $options = []): string
    {
        $exportOptions = array_merge([
            'pretty_print' => true,
            'json_flags' => JSON_UNESCAPED_UNICODE
        ], $options);

        return $this->export('json', $filters, $columns, $exportOptions);
    }

    /**
     * Exports data in CSV format with default settings.
     * 
     * @param array $filters Optional filters to apply
     * @param array $columns Optional columns to include
     * @param array $options Optional export options
     * @return string CSV formatted data
     */
    public function exportToCsv(array $filters = [], array $columns = [], array $options = []): string
    {
        $exportOptions = array_merge([
            'include_headers' => true
        ], $options);

        return $this->export('csv', $filters, $columns, $exportOptions);
    }

    /**
     * Exports data in XML format using custom strategy.
     * 
     * @param array $filters Optional filters to apply
     * @param array $columns Optional columns to include
     * @param array $options Optional export options
     * @return string XML formatted data
     */
    public function exportToXml(array $filters = [], array $columns = [], array $options = []): string
    {
        $exportOptions = array_merge([
            'root_element' => 'data',
            'item_element' => 'item',
            'include_attributes' => true,
            'pretty_print' => true
        ], $options);

        return $this->export('xml', $filters, $columns, $exportOptions);
    }

    /**
     * Gets available export formats.
     * 
     * @return array Array of available export formats
     */
    public function getAvailableFormats(): array
    {
        return $this->exportService->getAvailableFormats();
    }

    /**
     * Checks if a specific export format is available.
     * 
     * @param string $format The format to check
     * @return bool True if format is available
     */
    public function hasFormat(string $format): bool
    {
        return $this->exportService->hasFormat($format);
    }
}

/**
 * Custom XML Export Strategy
 * 
 * Demonstrates how to create a custom export strategy that implements
 * the ExportStrategyInterface. This strategy exports data to XML format
 * with customizable root elements and attributes.
 */
class XmlExportStrategy implements ExportStrategyInterface
{
    public static function key(): string
    {
        return 'xml';
    }

    /**
     * Exports data to XML format.
     * 
     * @param Collection $data The data to export
     * @param array $options Export options
     * @return string XML formatted data
     */
    public function export(Collection $data, array $options = []): string
    {
        if ($data->isEmpty()) {
            return '<?xml version="1.0" encoding="UTF-8"?><data></data>';
        }

        $rootElement = $options['root_element'] ?? 'data';
        $itemElement = $options['item_element'] ?? 'item';
        $includeAttributes = $options['include_attributes'] ?? true;
        $prettyPrint = $options['pretty_print'] ?? true;
        
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootElement}></{$rootElement}>");
        
        foreach ($data as $index => $item) {
            $itemData = $this->convertToArray($item);
            $itemNode = $xml->addChild($itemElement);
            
            if ($includeAttributes) {
                $itemNode->addAttribute('index', $index + 1);
            }
            
            $this->addXmlChildren($itemNode, $itemData);
        }
        
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $prettyPrint;
        $dom->loadXML($xml->asXML());
        
        return $dom->saveXML();
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
     * Recursively adds XML children to a node.
     * 
     * @param \SimpleXMLElement $parent The parent XML element
     * @param array $data The data to add
     * @return void
     */
    private function addXmlChildren(\SimpleXMLElement $parent, array $data): void
    {
        foreach ($data as $key => $value) {
            // Handle nested arrays/objects
            if (is_array($value) || is_object($value)) {
                $child = $parent->addChild($key);
                $this->addXmlChildren($child, (array) $value);
            } else {
                // Handle special characters and CDATA
                $value = htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
                $parent->addChild($key, $value);
            }
        }
    }
}

/**
 * Export Example Usage Class
 * 
 * Demonstrates how to use the ExportExample class with different
 * export formats and configurations.
 */
class ExportExampleUsage
{
    /**
     * Demonstrates basic export functionality.
     * 
     * @param Model $model The model to export
     * @return array Array of exported data in different formats
     */
    public static function demonstrateBasicExports(Model $model): array
    {
        $exportExample = new ExportExample($model);
        
        // Basic filters
        $filters = ['status' => 'active'];
        $columns = ['id', 'name', 'email', 'created_at'];
        
        return [
            'json' => $exportExample->exportToJson($filters, $columns),
            'csv' => $exportExample->exportToCsv($filters, $columns),
            'xml' => $exportExample->exportToXml($filters, $columns)
        ];
    }

    /**
     * Demonstrates advanced export with custom options.
     * 
     * @param Model $model The model to export
     * @return array Array of exported data with different configurations
     */
    public static function demonstrateAdvancedExports(Model $model): array
    {
        $exportExample = new ExportExample($model);
        
        // Complex filters
        $filters = [
            'status' => ['active', 'pending'],
            'created_at' => '2024-01-01'
        ];
        
        $columns = ['id', 'name', 'email', 'status', 'created_at'];
        
        return [
            'json_pretty' => $exportExample->exportToJson($filters, $columns, [
                'pretty_print' => true,
                'json_flags' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ]),
            
            'csv_no_headers' => $exportExample->exportToCsv($filters, $columns, [
                'include_headers' => false
            ]),
            
            'xml_custom' => $exportExample->exportToXml($filters, $columns, [
                'root_element' => 'users',
                'item_element' => 'user',
                'include_attributes' => true,
                'pretty_print' => true
            ])
        ];
    }

    /**
     * Demonstrates checking available formats.
     * 
     * @param Model $model The model to check
     * @return array Available formats and their status
     */
    public static function demonstrateFormatChecking(Model $model): array
    {
        $exportExample = new ExportExample($model);
        
        $availableFormats = $exportExample->getAvailableFormats();
        
        return [
            'available_formats' => $availableFormats,
            'has_json' => $exportExample->hasFormat('json'),
            'has_csv' => $exportExample->hasFormat('csv'),
            'has_xml' => $exportExample->hasFormat('xml'),
            'has_excel' => $exportExample->hasFormat('excel') // Should be false
        ];
    }
}
