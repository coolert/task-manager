<?php

namespace Database\Seeders\Concerns;

use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait BuildProject
{
    /**
     * @param array{
     * owner?: User,
     * member_count?: int,
     * labels_count?: int,
     * tasks_count?: int,
     * task_order_step?: int,
     * comments_min?: int,
     * comments_max?: int,
     * assignee_ratio?: int,
     * } $config
     */
    protected function BuildProject(array $config = []): Project
    {
        $config = array_merge([
            'owner'           => null,
            'member_count'    => fake()->numberBetween(3, 5),
            'labels_count'    => fake()->numberBetween(5, 7),
            'tasks_count'     => 20,
            'task_order_step' => 10,
            'assignee_ratio'  => 60,
        ], $config);

        return DB::transaction(function () use ($config) {
            // Owner
            $owner = $config['owner'] ?? User::factory()->create();

            // Project
            $project = Project::factory()->for($owner, 'owner')->create();

            // Project members
            $memberPool = $this->buildMembers($project, $owner, $config['member_count']);

            // Labels
            $this->buildLabels($project, $config['labels_count']);

            // Tasks
            $this->buildTasks($project, $memberPool, $config['tasks_count'], $config['task_order_step'], $config['assignee_ratio']);

            return $project;
        });
    }

    /**
     * @return Collection<int, User>
     */
    protected function buildMembers(Project $project, User $owner, int $count): Collection
    {
        ProjectMember::factory()
            ->for($project)
            ->for($owner)
            ->create([
                'role' => ProjectRole::Owner,
            ]);

        $people = User::factory()
            ->count($count)
            ->state(fn () => [
                'avatar_url' => fake()->boolean(80) ? 'https://api.dicebear.com/6.x/micah/jpg?seed=' . urlencode(fake()->unique()->userName()) : null,
            ])
            ->create();

        foreach ($people as $member) {
            ProjectMember::factory()
                ->for($project)
                ->for($member)
                ->create([
                    'role' => ProjectRole::Member,
                ]);
        }

        return collect([$owner])->merge($people)->values();
    }

    protected function buildLabels(Project $project, int $count): void
    {
        Label::factory()->for($project)->count($count)->create();
    }

    /**
     * @param  Collection<int, User>  $memberPool
     */
    protected function buildTasks(Project $project, Collection $memberPool, int $count, int $orderStep = 10, int $assigneeRatio = 60): void
    {
        Task::factory()
            ->count($count)
            ->for($project)
            ->sequence(fn ($seq) => [
                'order_no' => ($seq->index + 1) * $orderStep,
            ])
            ->state(function () use ($memberPool, $assigneeRatio) {
                $creatorId  = $memberPool->random()->id;
                $assigneeId = fake()->boolean($assigneeRatio) ? $memberPool->random()->id : null;

                $statusBucket = fake()->numberBetween(1, 10);
                $status       = match (true) {
                    $statusBucket <= 5 => TaskStatus::Todo,
                    $statusBucket <= 8 => TaskStatus::Doing,
                    default            => TaskStatus::Done
                };

                $priorityBucket = fake()->numberBetween(1, 8);
                $priority       = match (true) {
                    $priorityBucket <= 4 => TaskPriority::Low,
                    $priorityBucket <= 7 => TaskPriority::Normal,
                    default              => TaskPriority::High,
                };

                $dueBucket = fake()->numberBetween(1, 10);
                $dueDate   = match (true) {
                    $dueBucket <= 3 => null,
                    $dueBucket <= 7 => fake()->dateTimeBetween('+1 day', '+14 days'),
                    default         => fake()->dateTimeBetween('-7 days')
                };

                return [
                    'creator_id'  => $creatorId,
                    'status'      => $status,
                    'priority'    => $priority,
                    'assignee_id' => $assigneeId,
                    'due_date'    => $dueDate,
                ];
            })
            ->create();
    }
}
