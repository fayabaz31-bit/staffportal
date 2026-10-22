<?php

namespace Database\Factories;

use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveEntitlement>
 */
class LeaveEntitlementFactory extends Factory
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
            'leave_type_id' => LeaveType::factory(),
            'year' => 2026,
            'allocated_days' => 30,
            'used_days' => 0,
            'pending_days' => 0,
            'is_policy_configured' => true,
        ];
    }
}
