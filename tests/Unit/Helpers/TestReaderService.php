<?php

namespace SgFlores\Cruder\Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use SgFlores\Cruder\BaseReaderService;

/**
 * Test helper service that exposes protected methods from BaseReaderService for testing.
 */
class TestReaderService extends BaseReaderService
{
    public function __construct()
    {
        parent::__construct(new class extends Model {
            protected $table = 'test_models';
            public $timestamps = false;
        });
    }

    /**
     * Expose mergeBetweenFilter method for testing.
     */
    public function exposeMergeBetweenFilter(array &$filters, string $column, string $fromParam, string $toParam, ?string $dateFormat = null): void
    {
        $this->mergeBetweenFilter($filters, $column, $fromParam, $toParam, $dateFormat);
    }

    /**
     * Expose normalizeMonthFilter method for testing.
     */
    public function exposeNormalizeMonthFilter(
        array &$filters,
        string $monthParam,
        string $fromParam,
        string $toParam,
        ?string $dateFormat = 'Y-m-d'
    ): void {
        $this->normalizeMonthFilter($filters, $monthParam, $fromParam, $toParam, $dateFormat);
    }
}

