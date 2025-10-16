<?php

namespace App\Http\Services;

use App\DTOs\ProjectMemberStoreDTO;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectMemberService
{
    /**
     * @return LengthAwarePaginator<int, ProjectMember>
     */
    public function getMembers(Project $project): LengthAwarePaginator
    {
        return ProjectMember::query()
            ->where('project_id', $project->id)
            ->with(['user:id,name,avatar_url'])
            ->latest('id')
            ->paginate(10);
    }

    public function createMember(ProjectMemberStoreDTO $dto): ProjectMember
    {
        try {
            return ProjectMember::query()->create($dto->toModelArray())->load('user:id,name,avatar_url');

        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                abort(409, 'Member already exists in this project');
            }
            throw $e;
        }
    }

    public function updateMember(ProjectMember $projectMember, string $role): ProjectMember
    {
        $projectMember->fill(['role' => $role])->save();

        return $projectMember->fresh(['user:id,name,avatar_url']);
    }

    public function deleteMember(ProjectMember $projectMember): void
    {
        $projectMember->delete();
    }
}
