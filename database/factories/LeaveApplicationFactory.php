<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveApplication>
 */
class LeaveApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse('2026-11-02'); // Monday
        $end = Carbon::parse('2026-11-06'); // Friday

        return [
            'user_id' => User::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_requested' => 5,
            'reason' => 'Annual family vacation leave request.',
            'status' => LeaveApplication::STATUS_SUBMITTED,
            'current_stage' => LeaveApplication::STAGE_SUPERVISOR,
            'assigned_department_id' => Department::factory(),
            'concurrency_version' => 1,
        ];
    }
}
