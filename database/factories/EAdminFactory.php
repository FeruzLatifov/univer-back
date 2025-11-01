<?php

namespace Database\Factories;

use App\Models\EAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EAdmin>
 */
class EAdminFactory extends Factory
{
    protected $model = EAdmin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'login' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'), // Default password
            'full_name' => "$lastName $firstName",
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            '_role' => $this->faker->randomElement(['admin', 'teacher', 'dean', 'rector']),
            'status' => $this->faker->randomElement(['10', '11']), // Active statuses
            'active' => true,
            '_employee' => null, // Can be overridden
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
            'status' => '0',
        ]);
    }

    /**
     * Indicate that the admin is a specific role.
     */
    public function role(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            '_role' => $role,
        ]);
    }

    /**
     * Create an admin with employee relationship.
     */
    public function withEmployee(): static
    {
        return $this->state(function (array $attributes) {
            $employee = \App\Models\EEmployee::factory()->create();

            return [
                '_employee' => $employee->id,
            ];
        });
    }
}
