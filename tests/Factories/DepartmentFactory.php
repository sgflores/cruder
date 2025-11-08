<?php

namespace SgFlores\Cruder\Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SgFlores\Cruder\Tests\Models\Department;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\SgFlores\Cruder\Tests\Models\Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->lexify('???'),
            'description' => $this->faker->sentence(),
        ];
    }
}
