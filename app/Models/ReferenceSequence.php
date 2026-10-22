<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['scope', 'year', 'next_value'])]
class ReferenceSequence extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_value' => 'integer',
        ];
    }

    /**
     * Atomically reserve and return the next integer in a (scope, year) sequence.
     *
     * Callers that migrate from a legacy `count() + 1` scheme pass the current
     * number of issued references as `$floor` so the first generated value never
     * collides with an existing record. Once the row exists it is only ever
     * incremented under a row lock, so concurrent requests can never reuse or
     * skip a reference number.
     */
    public static function next(string $scope, int $year, int $floor = 0): int
    {
        return DB::transaction(function () use ($scope, $year, $floor): int {
            $row = static::query()
                ->where('scope', $scope)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                static::query()->insertOrIgnore([
                    'scope' => $scope,
                    'year' => $year,
                    'next_value' => max(0, $floor),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $row = static::query()
                    ->where('scope', $scope)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $row->next_value = $row->next_value + 1;
            $row->save();

            return $row->next_value;
        });
    }
}
