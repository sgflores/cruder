<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;

/**
 * Export service for managing different export strategies.
 * 
 * This service follows the Strategy pattern to provide flexible export
 * functionality. It can be easily extended with new export formats
 * without modifying existing code.
 */
class ExportService
{
    /**
     * Registered export strategies.
     * 
     * @var array<string, ExportStrategyInterface>
     */
    private array $strategies = [];

    /**
     * Adds an export strategy.
     * 
     * @param string $name The strategy name (optional, uses strategy's key() if not provided)
     * @param ExportStrategyInterface $strategy The strategy implementation
     * @return void
     */
    public function addStrategy(string $name, ExportStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    /**
     * Adds an export strategy using its key() method.
     * 
     * @param ExportStrategyInterface $strategy The strategy implementation
     * @return void
     */
    public function addStrategyByKey(ExportStrategyInterface $strategy): void
    {
        $this->strategies[$strategy::key()] = $strategy;
    }

    /**
     * Exports data using the specified strategy.
     * 
     * Uses the registered strategy for the given format to convert
     * the collection data into the desired export format (CSV, JSON, etc.).
     * 
     * @param string $format The export format
     * @param Collection $data The data to export
     * @param array $options Export options
     * @return string The exported data
     * @throws \InvalidArgumentException If the format is not supported
     */
    public function export(string $format, Collection $data, array $options = []): string
    {
        // Get the strategy for the requested format
        $strategy = $this->strategies[$format] ?? null;
        
        // Throw exception if format is not supported
        if (!$strategy) {
            throw new \InvalidArgumentException("Export format '{$format}' not supported. Available formats: " . implode(', ', $this->getAvailableFormats()));
        }
        
        // Delegate to the strategy to handle the actual export
        return $strategy->export($data, $options);
    }

    /**
     * Gets all registered format names.
     * 
     * @return array Array of format names
     */
    public function getAvailableFormats(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Checks if a format is registered.
     * 
     * @param string $format The format name
     * @return bool True if the format exists
     */
    public function hasFormat(string $format): bool
    {
        return isset($this->strategies[$format]);
    }
}
