<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectTransferRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Services\ProjectService;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectService $projectService): AnonymousResourceCollection
    {
        $user     = $request->user();
        $projects = $projectService->getProjects($user);

        return ProjectResource::collection($projects);
    }

    public function store(ProjectStoreRequest $request, ProjectService $projectService): JsonResponse
    {
        $dto     = $request->toDTO();
        $project = $projectService->createProject($dto);

        return ProjectResource::make($project)->response()->setStatusCode(201);
    }

    public function show(Project $project, ProjectService $projectService): ProjectResource
    {
        $this->authorize('view', $project);
        $project = $projectService->getProject($project);

        return ProjectResource::make($project);
    }

    public function update(ProjectUpdateRequest $request, Project $project, ProjectService $service): ProjectResource
    {
        $this->authorize('update', $project);
        $dto     = $request->toDTO();
        $project = $service->updateProject($project, $dto);

        return ProjectResource::make($project);
    }

    public function destroy(Project $project, ProjectService $service): Response
    {
        $this->authorize('delete', $project);
        $service->deleteProject($project);

        return response()->noContent();
    }

    public function transferOwnership(ProjectTransferRequest $request, Project $project, ProjectService $service): ProjectResource
    {
        $this->authorize('transferOwnership', $project);
        $new_owner_id = $request->validated('new_owner_id');
        $project      = $service->transferOwnership($project, $new_owner_id);

        return ProjectResource::make($project);
    }
}
