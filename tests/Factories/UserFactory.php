<?php

namespace SgFlores\Cruder\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SgFlores\Cruder\Tests\Models\Department;
use SgFlores\Cruder\Tests\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'department_id' => Department::factory(),
        ];
    }
}
