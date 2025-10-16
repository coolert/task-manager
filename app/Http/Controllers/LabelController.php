<?php

namespace App\Http\Controllers;

use App\Http\Requests\LabelStoreRequest;
use App\Http\Requests\LabelUpdateRequest;
use App\Http\Resources\LabelResource;
use App\Http\Services\LabelService;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LabelController extends Controller
{
    public function index(Project $project, LabelService $service): AnonymousResourceCollection
    {
        $this->authorize('viewLabels', $project);
        $labels = $service->getLabels($project);

        return LabelResource::collection($labels);
    }

    public function store(LabelStoreRequest $request, Project $project, LabelService $service): JsonResponse
    {
        $this->authorize('manageLabels', $project);
        $dto   = $request->toDTO();
        $label = $service->createLabel($dto);

        return LabelResource::make($label)->response()->setStatusCode(201);
    }

    public function update(LabelUpdateRequest $request, Project $project, Label $label, LabelService $service): LabelResource
    {
        $this->authorize('manageLabels', $project);
        $dto   = $request->toDTO();
        $label = $service->updateLabel($dto, $label);

        return LabelResource::make($label);
    }

    public function destroy(Project $project, Label $label, LabelService $service): Response
    {
        $this->authorize('manageLabels', $project);
        if ($label->taskLabels()->exists()) {
            abort(409, 'Label is in use by tasks. Detach or merge before delete.');
        }
        $service->deleteLabel($label);

        return response()->noContent();
    }
}
