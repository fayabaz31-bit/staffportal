<?php

namespace Database\Factories;

use App\Models\Payslip;
use App\Models\PayslipBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payslip_batch_id' => PayslipBatch::factory(),
            'user_id' => User::factory(),
            'staff_number' => fn (array $attributes) => User::find($attributes['user_id'])->staff_number ?? 'NFLV-0001',
            'payroll_period' => now()->format('Y-m'),
            'original_filename' => 'NFLV-0001.pdf',
            'storage_path' => 'payslips/'.now()->format('Y-m').'/'.fake()->uuid().'.pdf',
            'file_hash' => hash('sha256', fake()->text()),
            'file_size' => fake()->numberBetween(10000, 50000),
            'status' => Payslip::STATUS_PUBLISHED,
            'published_at' => now(),
            'download_count' => 0,
        ];
    }
}
