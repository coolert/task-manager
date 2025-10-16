<?php

namespace App\Http\Services;

use App\DTOs\LabelStoreDTO;
use App\DTOs\LabelUpdateDTO;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

class LabelService
{
    /**
     * @return LengthAwarePaginator<int, Label>
     */
    public function getLabels(Project $project): LengthAwarePaginator
    {
        return Label::query()
            ->where('project_id', $project->id)
            ->latest('id')
            ->paginate(20);
    }

    public function createLabel(LabelStoreDTO $dto): Label
    {
        return Label::query()->create($dto->toModelArray());
    }

    public function updateLabel(LabelUpdateDTO $dto, Label $label): Label
    {
        $label->fill($dto->toModelArray())->save();

        return $label->fresh();
    }

    public function deleteLabel(Label $label): void
    {
        $label->delete();
    }
}
