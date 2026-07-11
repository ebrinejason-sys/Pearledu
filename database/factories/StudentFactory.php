<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'emis_number' => fake()->unique()->numerify('EMIS######'),
            'status' => 'active',
            'lin' => null,
            'nin' => null,
            'class_id' => null,
            'user_id' => null,
        ];
    }
}
