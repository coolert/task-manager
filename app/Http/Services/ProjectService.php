<?php

namespace App\Http\Services;

use App\DTOs\ProjectStoreDTO;
use App\DTOs\ProjectUpdateDTO;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    /**
     * @return LengthAwarePaginator<int, Project>
     */
    public function getProjects(User $user): LengthAwarePaginator
    {
        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('projectMembers', fn ($query) => $query->where('user_id', $user->id))
            ->withCount(['tasks'])
            ->latest('id')
            ->distinct()
            ->paginate(10);
    }

    public function createProject(ProjectStoreDTO $dto): Project
    {
        return Project::query()->create($dto->toModelArray());
    }

    public function getProject(Project $project): Project
    {
        return $project->loadCount('tasks');
    }

    public function updateProject(Project $project, ProjectUpdateDTO $dto): Project
    {
        $project->fill($dto->toModelArray())->save();

        return $project->fresh();
    }

    public function deleteProject(Project $project): void
    {
        $project->delete();
    }

    public function transferOwnership(Project $project, int $new_owner_id): Project
    {
        $project->update(['owner_id' => $new_owner_id]);

        return $project->fresh();
    }
}
