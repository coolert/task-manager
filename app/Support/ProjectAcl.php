<?php

namespace App\Support;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectAcl
{
    public static function roleOf(Project $project, User $user): ?ProjectRole
    {
        if ($project->owner_id === $user->id) {
            return ProjectRole::Owner;
        }
        $projectMember = $project->projectMembers()->where('user_id', $user->id)->first();

        return $projectMember?->role;
    }

    public static function atLeast(ProjectRole $role, ProjectRole $min): bool
    {
        $rank = [
            ProjectRole::Viewer->value => 1,
            ProjectRole::Member->value => 2,
            ProjectRole::Admin->value  => 3,
            ProjectRole::Owner->value  => 4,
        ];

        return $rank[$role->value] >= $rank[$min->value];
    }
}
