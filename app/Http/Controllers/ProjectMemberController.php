<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectMemberStoreRequest;
use App\Http\Requests\ProjectMemberUpdateRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Http\Services\ProjectMemberService;
use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectMemberController extends Controller
{
    public function index(Project $project, ProjectMemberService $service): AnonymousResourceCollection
    {
        $this->authorize('manageMembers', $project);
        $members = $service->getMembers($project);

        return ProjectMemberResource::collection($members);
    }

    public function store(ProjectMemberStoreRequest $request, Project $project, ProjectMemberService $service): JsonResponse
    {
        $this->authorize('manageMembers', $project);
        if ($request->post('role') == 'owner') {
            abort(422, 'Owner cannot be created');
        }
        $dto    = $request->toDTO();
        $member = $service->createMember($dto);

        return ProjectMemberResource::make($member)->response()->setStatusCode(201);
    }

    public function update(ProjectMemberUpdateRequest $request, Project $project, ProjectMember $projectMember, ProjectMemberService $service): ProjectMemberResource
    {
        $this->authorize('manageMembers', $project);
        if ($request->post('role') == 'owner') {
            abort(422, 'Owner cannot be updated');
        }
        $role   = $request->validated('role');
        $member = $service->updateMember($projectMember, $role);

        return ProjectMemberResource::make($member);
    }

    public function destroy(Project $project, ProjectMember $projectMember, ProjectMemberService $service): Response
    {
        $this->authorize('manageMembers', $project);
        if ($projectMember->user_id === $project->owner_id) {
            abort(422, 'Owner cannot be removed. Transfer ownership first.');
        }
        $service->deleteMember($projectMember);

        return response()->noContent();
    }
}
