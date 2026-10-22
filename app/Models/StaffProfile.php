<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'department_id',
    'organisational_unit_id',
    'designation',
    'gender',
    'grade_level',
    'unit',
    'date_of_first_appointment',
    'date_of_birth',
    'marital_status',
    'next_of_kin',
    'next_of_kin_phone',
    'employment_status',
    'phone_number',
    'personal_email',
    'residential_address',
])]
class StaffProfile extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_first_appointment' => 'date',
            'date_of_birth' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<OrganisationalUnit, $this> */
    public function organisationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganisationalUnit::class);
    }
}
