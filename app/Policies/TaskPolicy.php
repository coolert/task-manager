<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\ProjectAcl;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        $project = $task->project;

        return $project && ($project->owner_id === $user->id || $project->projectMembers()->where('user_id', $user->id)->exists());
    }

    public function create(User $user, Project $project): bool
    {
        $role = ProjectAcl::roleOf($project, $user);

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin, ProjectRole::Member], true);
    }

    public function update(User $user, Task $task): bool
    {
        $project = $task->project;
        $role    = $project ? ProjectAcl::roleOf($project, $user) : null;

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin])
            || $task->creator_id  === $user->id
            || $task->assignee_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        $project = $task->project;
        $role    = $project ? ProjectAcl::roleOf($project, $user) : null;

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin])
            || $task->creator_id === $user->id;
    }

    public function assign(User $user, Task $task): bool
    {
        $project = $task->project;
        $role    = $project ? ProjectAcl::roleOf($project, $user) : null;

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin]);
    }

    public function claim(User $user, Task $task): bool
    {
        if ($task->assignee_id !== null) {
            return false;
        }

        $project = $task->project;
        $role    = $project ? ProjectAcl::roleOf($project, $user) : null;

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin, ProjectRole::Member]);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function label(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
