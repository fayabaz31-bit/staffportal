<?php

namespace Database\Factories;

use App\Models\OfficialLetter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialLetter>
 */
class OfficialLetterFactory extends Factory
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
            'reference_number' => 'NFLV/REG/2026/'.fake()->unique()->numerify('####'),
            'document_type' => 'leave_approval',
            'subject' => 'Approval of Annual Leave Application',
            'body_html' => '<p>Your annual leave application has been approved.</p>',
            'storage_path' => 'letters/2026/letter_'.fake()->uuid().'.pdf',
            'file_hash' => hash('sha256', fake()->text()),
            'file_size' => fake()->numberBetween(10000, 30000),
            'issued_by' => User::factory(),
            'issued_at' => now(),
        ];
    }
}
