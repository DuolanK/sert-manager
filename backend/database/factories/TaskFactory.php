<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'executor' => fake()->name(),
            'due_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'completed' => fake()->boolean(30),
        ];
    }
}
