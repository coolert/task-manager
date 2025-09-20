<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'creator_id'  => User::factory(),
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'status'      => $this->faker->randomElement(TaskStatus::cases()),
            'priority'    => $this->faker->randomElement(TaskPriority::cases()),
            'assignee_id' => null,
            'due_date'    => $this->faker->optional()->dateTimeBetween('+1 day', '+2 weeks'),
            'order_no'    => $this->faker->numberBetween(1, 9999),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Task $task): void {
            $existingLabelIds = Label::query()
                ->where('project_id', $task->project_id)
                ->pluck('id');

            if ($existingLabelIds->isEmpty()) {
                $existingLabelIds = Label::factory()
                    ->count($this->faker->numberBetween(2, 4))
                    ->create(['project_id' => $task->project_id])
                    ->pluck('id');
            }

            $attachIds = $existingLabelIds
                ->shuffle()
                ->take($this->faker->numberBetween(0, min(3, $existingLabelIds->count())))
                ->all();

            if (! empty($attachIds)) {
                $task->labels()->syncWithoutDetaching($attachIds);
            }

            $commentsCount = $this->faker->numberBetween(0, 3);
            if ($commentsCount > 0) {
                /** @var Collection<int, int> $memberIds */
                $memberIds = $task->project ? $task->project->projectMembers()->pluck('user_id') : collect();
                if ($memberIds->isNotEmpty()) {
                    TaskComment::factory()
                        ->count($commentsCount)
                        ->create([
                            'task_id' => $task->id,
                            'user_id' => $memberIds->random(),
                        ]);
                }
            }
        });
    }

    public function withComments(int $count = 2): static
    {
        return $this->afterCreating(function (Task $task) use ($count) {
            TaskComment::factory()->count($count)->create(['task_id' => $task->id]);
        });
    }
}
