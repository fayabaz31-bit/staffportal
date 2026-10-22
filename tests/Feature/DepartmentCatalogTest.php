<?php

namespace Tests\Feature;

use App\DepartmentCatalog;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Registry groups personnel by the institution's seven official
 * departments, in a fixed display order.
 */
class DepartmentCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_canonical_departments_are_created_in_display_order(): void
    {
        DepartmentCatalog::sync();

        $this->assertSame(
            [
                'ACADEMICS',
                'REGISTRY',
                'BURSARY',
                'HEALTH_SERVICES',
                'WORKS_SERVICES',
                'STAFF_SCHOOL',
                'DIRECTORATE',
            ],
            Department::ordered()->pluck('code')->all(),
        );

        $this->assertSame('Academics', DepartmentCatalog::name('ACADEMICS'));
        $this->assertSame('Health and Services', DepartmentCatalog::name('HEALTH_SERVICES'));
        $this->assertSame('Works and Services', DepartmentCatalog::name('WORKS_SERVICES'));
        $this->assertSame('Staff School', DepartmentCatalog::name('staff_school'));
    }

    public function test_syncing_is_idempotent_and_upgrades_a_legacy_code_with_the_same_name(): void
    {
        $legacy = Department::factory()->create([
            'code' => 'REG',
            'name' => 'Registry',
            'sort_order' => 9,
        ]);

        DepartmentCatalog::sync();
        DepartmentCatalog::sync();

        $this->assertSame(7, Department::query()->count());
        $this->assertSame('REGISTRY', $legacy->fresh()->code);
        $this->assertSame(1, $legacy->fresh()->sort_order);
        $this->assertSame(7, Department::query()->where('is_active', true)->count());
    }
}
