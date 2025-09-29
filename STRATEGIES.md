# CRUDer Strategy Patterns Documentation

## 🎯 Strategy Pattern Overview

The CRUDer package implements the **Strategy Pattern** to provide flexible, interchangeable algorithms for different operations. This allows you to:

- **Swap algorithms at runtime**
- **Add new strategies without modifying existing code**
- **Test strategies independently**
- **Maintain clean separation of concerns**

## 🔍 Search Strategies

### 1. **SearchStrategyInterface**

All search strategies must implement this interface:

```php
interface SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder;
}
```

#### **Parameters**
- `$query`: Eloquent query builder
- `$filters`: Array of all query options
- `$searchTerm`: Optional search term (can be extracted from `$filters['search']`)
- `$config`: Optional configuration (contains `direct_columns`, `related_columns`)

### 2. **LikeSearchStrategy** (Default)

Basic LIKE-based search strategy included by default.

#### **Features**
- **Direct Column Search**: Searches in declared direct columns
- **Related Column Search**: Searches in related model columns
- **Array Handling**: Handles array search terms by imploding them
- **Case Insensitive**: Uses LIKE with wildcards

#### **Configuration**
```php
// In your service class
protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'email'];
protected const RELATED_TEXT_SEARCH_COLUMNS = ['department_name'];
```

#### **Example Usage**
```php
// Search with default like strategy
$users = $userService->findAll([
    'search' => 'john',
    'status' => 'active'
]);
```

#### **Implementation**
```php
<?php

namespace SgFlores\Cruder\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

class LikeSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        // Use searchTerm parameter if provided, otherwise extract from filters
        $term = $searchTerm ?? $filters['search'] ?? '';
        
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
                $subQuery->orWhere($column, 'like', '%' . $term . '%');
            }
            
            // Search related columns
            foreach ($relatedColumns as $column) {
                $relationParts = $this->parseRelationColumn($column);
                if (!empty($relationParts['relation'])) {
                    $subQuery->orWhereHas($relationParts['relation'], function (Builder $relationQuery) use ($relationParts, $term) {
                        $relationQuery->where($relationParts['column'], 'like', '%' . $term . '%');
                    });
                }
            }
        });
        
        return $query;
    }
}
```

### 3. **Custom Search Strategies**

#### **ElasticsearchStrategy Example**
```php
<?php

namespace App\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

class ElasticsearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        $term = $searchTerm ?? $filters['search'] ?? '';
        
        if (empty($term)) {
            return $query;
        }
        
        // Use Elasticsearch for advanced search
        $elasticsearchResults = $this->searchElasticsearch($term, $filters);
        
        if (empty($elasticsearchResults)) {
            return $query->whereRaw('1 = 0'); // No results
        }
        
        // Filter by Elasticsearch results
        return $query->whereIn('id', $elasticsearchResults);
    }
    
    private function searchElasticsearch(string $term, array $filters): array
    {
        // Elasticsearch implementation
        // Return array of IDs
    }
}
```

#### **Advanced Search Strategy Example**
```php
<?php

namespace App\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

class AdvancedSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        $term = $searchTerm ?? $filters['search'] ?? '';
        
        if (empty($term)) {
            return $query;
        }
        
        // Advanced search logic
        $searchTerms = $this->prepareSearchTerms($term);
        $directColumns = $config['direct_columns'] ?? [];
        $relatedColumns = $config['related_columns'] ?? [];
        
        $query->where(function (Builder $subQuery) use ($searchTerms, $directColumns, $relatedColumns) {
            foreach ($searchTerms as $searchTerm) {
                // Direct column search with fuzzy matching
                foreach ($directColumns as $column) {
                    $subQuery->orWhere($column, 'like', '%' . $searchTerm . '%');
                    $subQuery->orWhere($column, 'like', '%' . $this->getFuzzyMatch($searchTerm) . '%');
                }
                
                // Related column search
                foreach ($relatedColumns as $column) {
                    $relationParts = $this->parseRelationColumn($column);
                    if (!empty($relationParts['relation'])) {
                        $subQuery->orWhereHas($relationParts['relation'], function (Builder $relationQuery) use ($relationParts, $searchTerm) {
                            $relationQuery->where($relationParts['column'], 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            }
        });
        
        return $query;
    }
    
    private function prepareSearchTerms(string $term): array
    {
        // Remove stop words, split into terms, etc.
        return array_filter(explode(' ', strtolower($term)));
    }
    
    private function getFuzzyMatch(string $term): string
    {
        // Implement fuzzy matching logic
        return $term;
    }
}
```

## 📤 Export Strategies

### 1. **ExportStrategyInterface**

All export strategies must implement this interface:

```php
interface ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string;
}
```

#### **Parameters**
- `$data`: Collection of data to export
- `$options`: Array of export options (headers, formatting, etc.)

### 2. **CsvExportStrategy**

CSV export strategy with proper escaping.

#### **Features**
- **Header Support**: Optional headers
- **Proper Escaping**: Handles special characters
- **Custom Delimiters**: Configurable delimiters
- **Column Selection**: Export specific columns only

#### **Example Usage**
```php
// Export to CSV
$csvData = $userService->export('csv', [], ['name', 'email']);

// Export with custom options
$csvData = $userService->export('csv', [
    'headers' => true,
    'delimiter' => ';',
    'columns' => ['name', 'email', 'department_name']
]);
```

#### **Implementation**
```php
<?php

namespace SgFlores\Cruder\Strategies\Export;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;

class CsvExportStrategy implements ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string
    {
        $headers = $options['headers'] ?? true;
        $delimiter = $options['delimiter'] ?? ',';
        $columns = $options['columns'] ?? [];
        
        $output = fopen('php://temp', 'r+');
        
        // Add headers if requested
        if ($headers && !empty($columns)) {
            fputcsv($output, $columns, $delimiter);
        }
        
        // Add data rows
        foreach ($data as $item) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->getColumnValue($item, $column);
            }
            fputcsv($output, $row, $delimiter);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    private function getColumnValue($item, string $column): string
    {
        // Handle direct columns
        if (isset($item->$column)) {
            return $this->formatValue($item->$column);
        }
        
        // Handle related columns
        $relationParts = $this->parseRelationColumn($column);
        if (!empty($relationParts['relation']) && isset($item->{$relationParts['relation']})) {
            $relation = $item->{$relationParts['relation']};
            return $this->formatValue($relation->{$relationParts['column']} ?? '');
        }
        
        return '';
    }
    
    private function formatValue($value): string
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        return (string) $value;
    }
}
```

### 3. **JsonExportStrategy**

JSON export strategy with formatting options.

#### **Features**
- **Pretty Print**: Formatted JSON output
- **Custom Flags**: JSON encoding flags
- **Column Selection**: Export specific columns only
- **Nested Data**: Handles related model data

#### **Example Usage**
```php
// Export to JSON
$jsonData = $userService->export('json', [], ['name', 'email']);

// Export with custom options
$jsonData = $userService->export('json', [
    'pretty' => true,
    'flags' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    'columns' => ['name', 'email', 'department_name']
]);
```

### 4. **Custom Export Strategies**

#### **ExcelExportStrategy Example**
```php
<?php

namespace App\Strategies\Export;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportStrategy implements ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $columns = $options['columns'] ?? [];
        $headers = $options['headers'] ?? true;
        
        $row = 1;
        
        // Add headers
        if ($headers && !empty($columns)) {
            foreach ($columns as $index => $column) {
                $sheet->setCellValueByColumnAndRow($index + 1, $row, $column);
            }
            $row++;
        }
        
        // Add data
        foreach ($data as $item) {
            foreach ($columns as $index => $column) {
                $value = $this->getColumnValue($item, $column);
                $sheet->setCellValueByColumnAndRow($index + 1, $row, $value);
            }
            $row++;
        }
        
        // Save to temporary file
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_export');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
}
```

## ✅ Validation Strategies

### 1. **ValidationStrategyInterface**

All validation strategies must implement this interface:

```php
interface ValidationStrategyInterface
{
    public function validate(array $data, string $operation): array;
}
```

#### **Parameters**
- `$data`: Data to validate
- `$operation`: Operation type ('create' or 'update')

### 2. **LaravelValidationStrategy** (Default)

Uses Laravel's built-in validation system.

#### **Features**
- **Laravel Rules**: Uses Laravel validation rules
- **Custom Messages**: Custom error messages
- **Conditional Rules**: Rules that apply based on conditions
- **File Validation**: File upload validation

#### **Example Usage**
```php
// Define validation rules in service
protected const CREATE_VALIDATION_RULES = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|string|min:8|confirmed'
];

protected const UPDATE_VALIDATION_RULES = [
    'name' => 'sometimes|string|max:255',
    'email' => 'sometimes|email|unique:users,email,{id}',
    'password' => 'sometimes|string|min:8|confirmed'
];
```

### 3. **Custom Validation Strategies**

#### **CustomValidationStrategy Example**
```php
<?php

namespace App\Strategies\Validation;

use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;

class CustomValidationStrategy implements ValidationStrategyInterface
{
    public function validate(array $data, string $operation): array
    {
        $errors = [];
        
        // Custom validation logic
        if ($operation === 'create') {
            $errors = array_merge($errors, $this->validateCreate($data));
        } else {
            $errors = array_merge($errors, $this->validateUpdate($data));
        }
        
        // Business rule validation
        $errors = array_merge($errors, $this->validateBusinessRules($data, $operation));
        
        return $errors;
    }
    
    private function validateCreate(array $data): array
    {
        $errors = [];
        
        // Custom create validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        return $errors;
    }
    
    private function validateBusinessRules(array $data, string $operation): array
    {
        $errors = [];
        
        // Business rule: User must be 18 or older
        if (isset($data['birth_date'])) {
            $age = Carbon::parse($data['birth_date'])->age;
            if ($age < 18) {
                $errors['birth_date'] = 'User must be 18 or older';
            }
        }
        
        // Business rule: Email domain validation
        if (isset($data['email'])) {
            $domain = substr(strrchr($data['email'], "@"), 1);
            if (!in_array($domain, ['company.com', 'partner.com'])) {
                $errors['email'] = 'Email must be from approved domains';
            }
        }
        
        return $errors;
    }
}
```

## 🔧 Strategy Registration

### 1. **In Service Constructor**

```php
protected function configureServices(): void
{
    // Add custom search strategies
    $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
    $this->searchService->addStrategy('advanced', new AdvancedSearchStrategy());
    
    // Add custom export strategies
    $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    $this->exportService->addStrategy('pdf', new PdfExportStrategy());
    
    // Add custom validation strategies
    $this->validationService->addStrategy('custom', new CustomValidationStrategy());
}
```

### 2. **Runtime Registration**

```php
// Add strategy at runtime
$userService->getSearchService()->addStrategy('custom', new CustomSearchStrategy());
$userService->getExportService()->addStrategy('excel', new ExcelExportStrategy());
```

### 3. **Strategy Selection**

```php
// Use specific strategy
$users = $userService->findAll([
    'search' => 'john',
    'search_strategy' => 'elasticsearch'
]);

// Export with specific strategy
$excelData = $userService->export('excel', [], ['name', 'email']);
```

## 🎯 Best Practices

### 1. **Strategy Design**
- **Single Responsibility**: Each strategy should have one clear purpose
- **Interface Compliance**: Always implement the required interface
- **Error Handling**: Handle errors gracefully
- **Documentation**: Document strategy behavior and parameters

### 2. **Configuration**
- **Flexible Options**: Support configuration options
- **Default Values**: Provide sensible defaults
- **Validation**: Validate configuration options
- **Examples**: Provide usage examples

### 3. **Performance**
- **Efficient Algorithms**: Use efficient algorithms
- **Memory Management**: Manage memory usage
- **Caching**: Cache results when appropriate
- **Optimization**: Optimize for common use cases

### 4. **Testing**
- **Unit Tests**: Test each strategy independently
- **Integration Tests**: Test strategy integration
- **Mocking**: Use mocks for dependencies
- **Coverage**: Ensure good test coverage

## 📚 Example Classes

For complete examples of strategy implementations, see:

- **[CustomSearchStrategy.php](src/Examples/CustomSearchStrategy.php)** - Advanced search strategy example
- **[CustomExportStrategy.php](src/Examples/CustomExportStrategy.php)** - Custom export strategy example
- **[CustomValidationStrategy.php](src/Examples/CustomValidationStrategy.php)** - Custom validation strategy example
- **[ServiceUsageExample.php](src/Examples/ServiceUsageExample.php)** - Strategy usage examples

## 🔗 Related Documentation

- **[SERVICES.md](SERVICES.md)** - Service documentation
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Overall architecture
- **[EXAMPLES.md](EXAMPLES.md)** - Complete example classes

---

This documentation provides a comprehensive guide to all strategy patterns in the CRUDer package, their implementations, and usage patterns.
