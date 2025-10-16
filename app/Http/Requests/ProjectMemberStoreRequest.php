<?php

namespace App\Http\Requests;

use App\DTOs\ProjectMemberStoreDTO;
use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMemberStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('project_members', 'user_id')
                    ->where(fn ($q) => $q->where('project_id', $project->id)),
            ],
            'role' => ['required', 'in:' . implode(',', array_column(ProjectRole::cases(), 'value'))],
        ];
    }

    public function toDTO(): ProjectMemberStoreDTO
    {
        /** @var Project $project */
        $project = $this->route('project');
        $data    = array_merge($this->validated(), ['project_id' => $project->id]);

        return ProjectMemberStoreDTO::fromArray($data);
    }
}
