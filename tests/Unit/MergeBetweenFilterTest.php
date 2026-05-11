<?php

namespace SgFlores\Cruder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SgFlores\Cruder\Tests\Unit\Helpers\TestReaderService;

class MergeBetweenFilterTest extends TestCase
{
    private TestReaderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TestReaderService;
    }

    public function test_applies_between_operator_when_both_bounds_provided(): void
    {
        $filters = [
            'from_date' => '2025-01-01',
            'to_date' => '2025-01-31',
        ];

        $this->service->exposeMergeBetweenFilter($filters, 'invoices.billing_anchor', 'from_date', 'to_date');

        $this->assertArrayHasKey('invoices.billing_anchor', $filters);
        $this->assertSame(
            [
                'operator' => 'between',
                'value' => ['2025-01-01', '2025-01-31'],
            ],
            $filters['invoices.billing_anchor']
        );
        $this->assertArrayNotHasKey('from_date', $filters);
        $this->assertArrayNotHasKey('to_date', $filters);
    }

    public function test_applies_greater_than_equal_when_only_lower_bound_provided(): void
    {
        $filters = [
            'from_amount' => 100,
            'to_amount' => null,
        ];

        $this->service->exposeMergeBetweenFilter($filters, 'orders.total', 'from_amount', 'to_amount');

        $this->assertArrayHasKey('orders.total', $filters);
        $this->assertSame(
            [
                'operator' => 'gte',
                'value' => 100,
            ],
            $filters['orders.total']
        );
        $this->assertArrayNotHasKey('from_amount', $filters);
        $this->assertArrayNotHasKey('to_amount', $filters);
    }

    public function test_applies_less_than_equal_when_only_upper_bound_provided(): void
    {
        $filters = [
            'from_amount' => null,
            'to_amount' => 500,
        ];

        $this->service->exposeMergeBetweenFilter($filters, 'orders.total', 'from_amount', 'to_amount');

        $this->assertArrayHasKey('orders.total', $filters);
        $this->assertSame(
            [
                'operator' => 'lte',
                'value' => 500,
            ],
            $filters['orders.total']
        );
        $this->assertArrayNotHasKey('from_amount', $filters);
        $this->assertArrayNotHasKey('to_amount', $filters);
    }

    public function test_no_filter_applied_when_bounds_empty(): void
    {
        $filters = [
            'start' => null,
            'end' => '',
        ];

        $this->service->exposeMergeBetweenFilter($filters, 'logs.created_at', 'start', 'end');

        $this->assertArrayNotHasKey('logs.created_at', $filters);
        $this->assertArrayNotHasKey('start', $filters);
        $this->assertArrayNotHasKey('end', $filters);
    }

    public function test_formats_dates_when_format_provided(): void
    {
        $filters = [
            'from_date' => '2025-05-01 14:30:00',
            'to_date' => '2025-05-31 23:59:59',
        ];

        $this->service->exposeMergeBetweenFilter($filters, 'reports.generated_at', 'from_date', 'to_date', 'Y-m-d');

        $this->assertSame(
            [
                'operator' => 'between',
                'value' => ['2025-05-01', '2025-05-31'],
            ],
            $filters['reports.generated_at']
        );
    }
}
