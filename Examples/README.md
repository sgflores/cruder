# Real-World Examples for BaseCrudService and BaseReaderService

This directory contains comprehensive examples demonstrating how to use the `BaseCrudService` and `BaseReaderService` classes in real-world scenarios.

## 📁 Available Examples

### 1. **SimpleSalesExample.php** - Basic Usage
A straightforward example showing the core functionality of both services with a simple sales system.

**What it demonstrates:**
- Basic CRUD operations (Create, Read, Update, Delete)
- Search and filtering capabilities
- Order processing with relationships
- Bulk operations
- Advanced queries
- Event-driven business logic

**File:** [SimpleSalesExample.php](SimpleSalesExample.php)

### 2. **SalesInventoryExample.php** - Advanced Usage
A comprehensive example of a complete sales and inventory management system.

**What it demonstrates:**
- Product management with categories and inventory
- Order processing with automatic inventory updates
- Customer management
- Sales analytics and reporting
- Performance monitoring and caching
- Complex event-driven relationships

**File:** [SalesInventoryExample.php](SalesInventoryExample.php)

### 3. **ReportExample.php** - Custom Search Strategy
Demonstrates how to create custom search strategies for complex reporting and business intelligence.

**What it demonstrates:**
- Custom search strategy implementation
- Sales reporting with advanced queries
- Customer analytics and insights
- Product performance analysis
- Monthly sales trends
- Top customers and low-performing products

**File:** [ReportExample.php](ReportExample.php)

### 4. **ProductExample.php** - Custom Validation Strategy
Shows how to implement custom validation strategies and array-based validation rules.

**What it demonstrates:**
- Custom validation strategy implementation
- Array-based validation rules
- Business rule validation
- Conditional validation logic
- Product creation and update with validation
- Advanced validation scenarios

**File:** [ProductExample.php](ProductExample.php)

### 5. **ExportExample.php** - Data Export Functionality
Demonstrates how to export data using both default and custom export strategies.

**What it demonstrates:**
- Default JSON and CSV export strategies
- Custom XML export strategy implementation
- Using BaseReaderService built-in export functionality
- Custom export options and formatting
- Export strategy registration and management

**File:** [ExportExample.php](ExportExample.php)


## 📋 Key Concepts Demonstrated

### 1. **Service Configuration**
Each service class extends either `BaseCrudService` or `BaseReaderService` and defines column constants for security and validation.

### 2. **Basic CRUD Operations**
Complete Create, Read, Update, Delete operations with proper error handling and validation.

### 3. **Search and Filtering**
Advanced search capabilities including text search, price ranges, date filtering, and pagination.

### 4. **Data Export**
Flexible export functionality with multiple formats (JSON, CSV, XML) and custom export strategies.

### 5. **Event-Driven Relationships**
Using the built-in event system to handle complex relationships and business logic automatically.

### 6. **Bulk Operations**
Efficient bulk create, update, and delete operations for handling large datasets.

## 🔧 Advanced Features

### 1. **Column Validation**
The services automatically validate that only declared columns can be used for filtering, sorting, or searching.

### 2. **Performance Monitoring**
Built-in query logging, caching, and chunked processing for optimal performance.

### 3. **API Resources**
Support for Laravel API resources to transform responses.

## 📊 Business Logic Patterns

### 1. **Order Processing**
Complete order lifecycle management with automatic item handling and inventory updates.

### 2. **Inventory Management**
Real-time inventory tracking with low stock alerts and automatic restocking.

### 3. **Customer Management**
Customer registration with profile creation and order history tracking.

## 🎯 Best Practices

### 1. **Service Organization**
- Create separate services for each model
- Use descriptive service names (e.g., `ProductService`, `OrderService`)
- Define column constants for security and validation

### 2. **Event Handling**
- Use events for relationship management
- Keep event listeners focused and single-purpose
- Handle errors gracefully in event listeners

### 3. **Performance**
- Enable caching for frequently accessed data
- Use pagination for large datasets
- Consider chunked processing for bulk operations

### 4. **Security**
- Always define filterable, sortable, and searchable columns
- Validate input data
- Use database transactions for complex operations

## 🔍 Troubleshooting

### Common Issues

1. **Column Validation Errors**
   ```
   InvalidArgumentException: Filtered column 'invalid_column' is not declared
   ```
   **Solution:** Add the column to the appropriate constant array.

2. **Event Listeners Not Firing**
   **Solution:** Ensure the event listener is set up before calling the CRUD method.

3. **Performance Issues**
   **Solution:** Enable caching, use pagination, or implement chunked processing.

## 📚 Additional Resources

- [BaseCrudService Documentation](../src/BaseCrudService.php)
- [BaseReaderService Documentation](../src/BaseReaderService.php)
- [Relationship Handling Guide](../RELATIONSHIPS.md)
- [Architecture Overview](../ARCHITECTURE.md)

## 🤝 Contributing

Feel free to add more examples or improve existing ones. When adding new examples:

1. Follow the existing naming conventions
2. Include comprehensive comments
3. Demonstrate real-world scenarios
4. Show both basic and advanced usage patterns
