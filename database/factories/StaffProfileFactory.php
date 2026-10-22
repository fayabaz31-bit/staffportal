<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'designation' => fake()->jobTitle(),
            'employment_status' => 'employed',
            'phone_number' => '+234 800 000 0000',
            'personal_email' => fake()->safeEmail(),
            'residential_address' => fake()->address(),
        ];
    }
}
