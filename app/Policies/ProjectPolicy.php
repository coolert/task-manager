<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Support\ProjectAcl;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->projectMembers()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        $role = ProjectAcl::roleOf($project, $user);

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin], true);
    }

    public function delete(User $user, Project $project): bool
    {
        return ProjectAcl::roleOf($project, $user) === ProjectRole::Owner;
    }

    public function manageMembers(User $user, Project $project): bool
    {
        $role = ProjectAcl::roleOf($project, $user);

        return in_array($role, [ProjectRole::Owner, ProjectRole::Admin], true);
    }

    public function transferOwnership(User $user, Project $project): bool
    {
        return ProjectAcl::roleOf($project, $user) === ProjectRole::Owner;
    }
}
