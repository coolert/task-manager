<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'project_id' => $this->project_id,
            'creator'    => $this->whenLoaded('creator', fn () => [
                'id'         => $this->creator->id,
                'name'       => $this->creator->name,
                'avatar_url' => $this->creator->avatar_url,
            ]),
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'assignee'    => $this->whenLoaded('assignee', fn () => [
                'id'         => $this->assignee->id,
                'name'       => $this->assignee->name,
                'avatar_url' => $this->assignee->avatar_url,
            ]),
            'labels'     => LabelResource::collection($this->whenLoaded('labels', fn () => $this->labels->values())),
            'due_date'   => optional($this->due_date)?->toJSON(),
            'order_no'   => $this->order_no,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
