<?php

namespace App;

use App\Models\Department;

/**
 * Canonical institutional departments.
 *
 * The Registry groups every staff record by department, and the personnel
 * import matches staff to a department by code. Keeping the list in one place
 * means the seeder, the import, the directory and the tests all agree on the
 * institution's official departments and their display order.
 */
final class DepartmentCatalog
{
    public const string ACADEMICS = 'ACADEMICS';

    public const string REGISTRY = 'REGISTRY';

    public const string BURSARY = 'BURSARY';

    public const string HEALTH_AND_SERVICES = 'HEALTH_SERVICES';

    public const string WORKS_AND_SERVICES = 'WORKS_SERVICES';

    public const string STAFF_SCHOOL = 'STAFF_SCHOOL';

    public const string DIRECTORATE = 'DIRECTORATE';

    /**
     * The official departments in display order.
     *
     * @return array<string, string> code => name
     */
    public static function all(): array
    {
        return [
            self::ACADEMICS => 'Academics',
            self::REGISTRY => 'Registry',
            self::BURSARY => 'Bursary',
            self::HEALTH_AND_SERVICES => 'Health and Services',
            self::WORKS_AND_SERVICES => 'Works and Services',
            self::STAFF_SCHOOL => 'Staff School',
            self::DIRECTORATE => 'Directorate',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function name(string $code): string
    {
        return self::all()[strtoupper($code)] ?? $code;
    }

    /**
     * Create or update the canonical departments, preserving their order.
     */
    public static function sync(): void
    {
        $order = 0;

        foreach (self::all() as $code => $name) {
            $department = Department::query()
                ->where('code', $code)
                ->orWhere('name', $name)
                ->first();

            if ($department === null) {
                Department::query()->create([
                    'code' => $code,
                    'name' => $name,
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
            } else {
                $department->update([
                    'code' => $code,
                    'name' => $name,
                    'sort_order' => $order,
                    'is_active' => true,
                ]);
            }

            $order++;
        }
    }
}
