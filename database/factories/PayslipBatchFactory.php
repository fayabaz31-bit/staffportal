<?php

namespace Database\Factories;

use App\Models\PayslipBatch;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayslipBatch>
 */
class PayslipBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploaded_by' => User::factory()->create(['role' => UserRole::BursaryAdmin]),
            'payroll_period' => now()->format('Y-m'),
            'original_filename' => 'payslips_'.now()->format('Y_m').'.zip',
            'status' => PayslipBatch::STATUS_STAGED,
            'total_files' => 1,
            'matched_files' => 1,
            'error_count' => 0,
            'reconciliation_summary' => [],
        ];
    }
}
