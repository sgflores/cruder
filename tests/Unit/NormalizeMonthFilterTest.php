<?php

namespace SgFlores\Cruder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SgFlores\Cruder\Tests\Unit\Helpers\TestReaderService;

class NormalizeMonthFilterTest extends TestCase
{
    private TestReaderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TestReaderService();
    }

    public function testConvertsMonthToStartAndEndOfMonth(): void
    {
        $filters = [
            'month' => '2025-11',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertArrayHasKey('date_from', $filters);
        $this->assertArrayHasKey('date_to', $filters);
        $this->assertEquals('2025-11-01', $filters['date_from']);
        $this->assertEquals('2025-11-30', $filters['date_to']);
        $this->assertArrayNotHasKey('month', $filters);
    }

    public function testHandlesMonthWithDayInput(): void
    {
        $filters = [
            'month' => '2025-11-15',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2025-11-01', $filters['date_from']);
        $this->assertEquals('2025-11-30', $filters['date_to']);
        $this->assertArrayNotHasKey('month', $filters);
    }

    public function testHandlesMonthWithFirstDayInput(): void
    {
        $filters = [
            'month' => '2025-11-01',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2025-11-01', $filters['date_from']);
        $this->assertEquals('2025-11-30', $filters['date_to']);
    }

    public function testHandlesFebruaryLeapYear(): void
    {
        $filters = [
            'month' => '2024-02',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2024-02-01', $filters['date_from']);
        $this->assertEquals('2024-02-29', $filters['date_to']); // Leap year
    }

    public function testHandlesFebruaryNonLeapYear(): void
    {
        $filters = [
            'month' => '2025-02',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2025-02-01', $filters['date_from']);
        $this->assertEquals('2025-02-28', $filters['date_to']); // Non-leap year
    }

    public function testHandlesMonthsWith31Days(): void
    {
        $filters = [
            'month' => '2025-01',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2025-01-01', $filters['date_from']);
        $this->assertEquals('2025-01-31', $filters['date_to']);
    }

    public function testHandlesMonthsWith30Days(): void
    {
        $filters = [
            'month' => '2025-04',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertEquals('2025-04-01', $filters['date_from']);
        $this->assertEquals('2025-04-30', $filters['date_to']);
    }

    public function testNoFilterAppliedWhenMonthEmpty(): void
    {
        $filters = [
            'month' => null,
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertArrayNotHasKey('date_from', $filters);
        $this->assertArrayNotHasKey('date_to', $filters);
        $this->assertArrayNotHasKey('month', $filters);
    }

    public function testNoFilterAppliedWhenMonthIsEmptyString(): void
    {
        $filters = [
            'month' => '',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertArrayNotHasKey('date_from', $filters);
        $this->assertArrayNotHasKey('date_to', $filters);
        $this->assertArrayNotHasKey('month', $filters);
    }

    public function testNoFilterAppliedWhenMonthNotProvided(): void
    {
        $filters = [];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertArrayNotHasKey('date_from', $filters);
        $this->assertArrayNotHasKey('date_to', $filters);
    }

    public function testHandlesInvalidMonthFormat(): void
    {
        $filters = [
            'month' => 'invalid-date',
        ];

        // When invalid format is provided, month parameter should be removed
        // but no date filters should be set
        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to');

        $this->assertArrayNotHasKey('date_from', $filters);
        $this->assertArrayNotHasKey('date_to', $filters);
        $this->assertArrayNotHasKey('month', $filters);
    }

    public function testUsesCustomDateFormat(): void
    {
        $filters = [
            'month' => '2025-11',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'month', 'date_from', 'date_to', 'Y/m/d');

        $this->assertEquals('2025/11/01', $filters['date_from']);
        $this->assertEquals('2025/11/30', $filters['date_to']);
    }

    public function testUsesCustomParameterNames(): void
    {
        $filters = [
            'period' => '2025-11',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'period', 'start_date', 'end_date');

        $this->assertArrayHasKey('start_date', $filters);
        $this->assertArrayHasKey('end_date', $filters);
        $this->assertEquals('2025-11-01', $filters['start_date']);
        $this->assertEquals('2025-11-30', $filters['end_date']);
        $this->assertArrayNotHasKey('period', $filters);
    }

    public function testHandlesDifferentMonthParamName(): void
    {
        $filters = [
            'invoice_month' => '2025-12',
        ];

        $this->service->exposeNormalizeMonthFilter($filters, 'invoice_month', 'invoice_date_from', 'invoice_date_to');

        $this->assertEquals('2025-12-01', $filters['invoice_date_from']);
        $this->assertEquals('2025-12-31', $filters['invoice_date_to']);
        $this->assertArrayNotHasKey('invoice_month', $filters);
    }
}

