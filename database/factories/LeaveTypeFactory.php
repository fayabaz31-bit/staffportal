<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Annual Leave',
            'code' => 'ANNUAL_'.fake()->unique()->randomNumber(4),
            'description' => 'Annual recreational leave.',
            'default_days_per_year' => 30,
            'requires_attachment' => false,
            'requires_supervisor_review' => true,
            'requires_hr_verification' => true,
            'is_active' => true,
        ];
    }
}
