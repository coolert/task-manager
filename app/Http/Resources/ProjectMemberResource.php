<?php

namespace App\Http\Resources;

use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectMember
 */
class ProjectMemberResource extends JsonResource
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
            'user'       => $this->whenLoaded('user', fn () => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ]),
            'role'       => $this->role,
            'created_at' => $this->created_at->toJSON(),
            'updated_at' => $this->updated_at->toJSON(),
        ];
    }
}
