# Real-World Examples for BaseCrudService and BaseReaderService

This directory contains comprehensive examples demonstrating how to use the `BaseCrudService` and `BaseReaderService` classes in real-world scenarios with proper Laravel dependency injection.

## 📁 Available Examples

### 1. **SimpleSalesExample.php** - Basic Usage with Dependency Injection
A straightforward example showing the core functionality of both services.

**What it demonstrates:**
- Basic CRUD operations (Create, Read, Update, Delete)
- Event-driven business logic with EventService
- Validation with ValidationService
- Order processing with relationships
- Bulk operations
- Advanced queries

**File:** [SimpleSalesExample.php](SimpleSalesExample.php)

### 2. **SalesInventoryExample.php** - Advanced Usage with Full Service Integration
A comprehensive example of a complete sales and inventory management system with all services properly injected.

**What it demonstrates:**
- Full service integration (SearchService, ExportService, EventService, QueryLogger)
- Product management with categories and inventory
- Order processing with automatic inventory updates
- Customer management
- Sales analytics and reporting
- Performance monitoring and caching
- Complex event-driven relationships

**File:** [SalesInventoryExample.php](SalesInventoryExample.php)

### 3. **SalesReportService.php** - Custom Search Strategies for Reporting
Demonstrates how to create a reporting service with custom search strategies using the new strategy pattern.

**What it demonstrates:**
- Custom search strategies
- TopOrdersStrategy for finding top performing orders
- TopSalesStrategy for finding top sales by various criteria
- Event-driven reporting with EventService
- Export capabilities for reports
- Strategy registration

**File:** [SalesReportService.php](SalesReportService.php)

### 4. **ProductExample.php** - Custom Validation Strategy
Shows how to implement custom validation strategies.

**What it demonstrates:**
- Custom validation strategy implementation
- Array-based validation rules
- Business rule validation
- Conditional validation logic
- Product creation and update with validation

**File:** [ProductExample.php](ProductExample.php)

### 5. **ExportExample.php** - Data Export Functionality with Full Services
Demonstrates how to export data using both default and custom export strategies with all services injected.

**What it demonstrates:**
- Full service integration for export functionality
- Default JSON and CSV export strategies
- Custom XML export strategy implementation

**File:** [ExportExample.php](ExportExample.php)


## 📋 Key Concepts Demonstrated

### 1. **Service Configuration**
Each service class extends either `BaseCrudService` or `BaseReaderService` and defines column constants for security and validation.

### 2. **Basic CRUD Operations**
Complete Create, Read, Update, Delete operations with proper error handling and validation using injected services.

### 3. **Search and Filtering**
Advanced search capabilities including text search, price ranges, date filtering, and pagination with SearchService.

### 4. **Data Export**
Flexible export functionality with multiple formats (JSON, CSV, XML).

### 5. **Event-Driven Relationships**
Using the built-in event system with EventService to handle complex relationships and business logic automatically.

### 6. **Custom Search Strategies**
Pluggable search strategies for complex reporting queries and data analysis.

## 🔧 Advanced Features

### 1. **Column Validation**
The services automatically validate that only declared columns can be used for filtering, sorting, or searching.

### 2. **Performance Monitoring**
Built-in query logging with QueryLogger, caching, and chunked processing for optimal performance.

### 3. **Strategy Pattern Implementation**
Custom search and export strategies for better maintainability and type safety.

### 4. **Per-Use Service Validation**
Services validate their dependencies at runtime and provide clear error messages when required services are not injected.

## 📊 Business Logic Patterns

### 1. **Order Processing**
Complete order lifecycle management with automatic item handling and inventory updates.

### 2. **Inventory Management**
Real-time inventory tracking with low stock alerts and automatic restocking.

### 3. **Customer Management**
Customer registration with profile creation and order history tracking.

### 4. **Sales Analytics & Reporting**
Advanced reporting with custom search strategies for business intelligence and data analysis.

## 📈 SalesReportService Methods

The `SalesReportService` provides two main reporting methods with custom search strategies:

### 1. **getTopOrders(array $options = [])**
Returns top performing orders using the TopOrdersStrategy:
- Orders sorted by total amount (descending)
- Date range filtering with `date_from` and `date_to`
- Minimum amount filtering with `min_amount`
- Status filtering with `status`
- Limit control with `limit` parameter
- Automatic filtering of completed/shipped/delivered orders

### 2. **getTopSales(array $options = [])**
Returns top sales analysis using the TopSalesStrategy:
- Sales data with grouping capabilities (`group_by` parameter)
- Customer-based grouping with aggregated totals
- Date-based grouping (monthly, daily)
- Product filtering through order items relationship
- Customer filtering with `customer_id`
- Automatic filtering of completed sales

### Additional Methods:
- **exportSalesReport(string $format, array $options = [])** - Export functionality with custom strategies
- Uses `findAll(['strategies' => 'top_orders'])` and `findAll(['strategies' => 'top_sales'])` patterns
