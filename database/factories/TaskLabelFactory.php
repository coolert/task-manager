<?php

namespace Database\Factories;

use App\Models\Label;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskLabel>
 */
class TaskLabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id'  => Task::factory(),
            'label_id' => Label::factory(),
        ];
    }
}
