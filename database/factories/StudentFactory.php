<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(), 'email' => fake()->unique()->safeEmail(),
            'student_number' => 'NFLV/S/'.fake()->unique()->numerify('######'),
            'password' => 'Student-test-2026', 'programme' => 'Language immersion',
            'session' => '2026/2027', 'status' => 'active',
        ];
    }
}
