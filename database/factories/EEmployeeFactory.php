<?php

namespace Database\Factories;

use App\Models\EEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EEmployee>
 */
class EEmployeeFactory extends Factory
{
    protected $model = EEmployee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'second_name' => $this->faker->lastName(),
            'third_name' => $this->faker->firstName() . 'ovich',
            'employee_id_number' => 'EMP' . $this->faker->unique()->numberBetween(1000, 9999),
            'birth_date' => $this->faker->date('Y-m-d', '-25 years'),
            'hire_date' => $this->faker->date('Y-m-d', '-5 years'),
            '_gender' => $this->faker->randomElement(['11', '12']),
            '_country' => '182', // Uzbekistan
            'passport_number' => strtoupper($this->faker->bothify('??#######')),
            'passport_pin' => $this->faker->numerify('##############'),
            'image' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
