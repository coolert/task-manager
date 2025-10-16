<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'owner_id'    => $this->owner_id,
            'tasks_count' => $this->tasks_count ?? 0,
            'created_at'  => $this->created_at?->toJSON(),
            'updated_at'  => $this->updated_at?->toJSON(),
        ];
    }
}
