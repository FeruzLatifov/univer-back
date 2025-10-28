<?php

namespace Database\Factories;

use App\Models\EStudent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EStudent>
 */
class EStudentFactory extends Factory
{
    protected $model = EStudent::class;

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
            'student_id_number' => 'ST' . $this->faker->unique()->numberBetween(10000, 99999),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            '_gender' => $this->faker->randomElement(['11', '12']), // Male/Female codes
            '_country' => '182', // Uzbekistan
            'passport_number' => strtoupper($this->faker->bothify('??#######')),
            'passport_pin' => $this->faker->numerify('##############'),
            'phone_number' => '+998' . $this->faker->numerify('#########'),
            'phone_secondary' => $this->faker->optional()->numerify('+998#########'),
            'email' => $this->faker->optional()->safeEmail(),
            'telegram_username' => $this->faker->optional()->userName(),
            'password' => Hash::make('password'), // Default password
            'image' => null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the student is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the student is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            '_gender' => '11',
            'first_name' => $this->faker->firstNameMale(),
        ]);
    }

    /**
     * Indicate that the student is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            '_gender' => '12',
            'first_name' => $this->faker->firstNameFemale(),
        ]);
    }

    /**
     * Create a student with meta data.
     */
    public function withMeta(): static
    {
        return $this->afterCreating(function (EStudent $student) {
            \App\Models\EStudentMeta::factory()->create([
                '_student' => $student->id,
            ]);
        });
    }
}
