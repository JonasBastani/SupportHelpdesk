<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ListQueryHelper;
use Tests\TestCase;

class ListQueryHelperTest extends TestCase
{
    public function test_it_normalizes_sort_by_using_the_allowed_fields(): void
    {
        $this->assertSame(
            'status',
            ListQueryHelper::normalizeSortBy('status', ['created_at', 'status'], 'created_at'),
        );

        $this->assertSame(
            'created_at',
            ListQueryHelper::normalizeSortBy('priority', ['created_at', 'status'], 'created_at'),
        );
    }

    public function test_it_normalizes_sort_direction(): void
    {
        $this->assertSame('asc', ListQueryHelper::normalizeSortDirection('asc'));
        $this->assertSame('desc', ListQueryHelper::normalizeSortDirection('sideways'));
    }

    public function test_it_normalizes_per_page_with_default_and_maximum_limits(): void
    {
        $this->assertSame(10, ListQueryHelper::normalizePerPage(null));
        $this->assertSame(25, ListQueryHelper::normalizePerPage('25'));
        $this->assertSame(100, ListQueryHelper::normalizePerPage('200'));
        $this->assertSame(1, ListQueryHelper::normalizePerPage('0'));
    }
}
