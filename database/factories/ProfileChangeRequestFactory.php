<?php

namespace Database\Factories;

use App\Models\ProfileChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileChangeRequest>
 */
class ProfileChangeRequestFactory extends Factory
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
            'requested_by' => User::factory(),
            'field_key' => 'name',
            'current_value' => fake()->name(),
            'proposed_value' => fake()->name(),
            'reason' => fake()->sentence(),
            'status' => ProfileChangeRequest::STATUS_PENDING,
            'current_stage' => ProfileChangeRequest::STAGE_HEAD_OF_HR,
        ];
    }
}
