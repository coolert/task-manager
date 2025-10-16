<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskAssignRequest extends FormRequest
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
        /** @var Project|null $project */
        $project = $this->route('task')?->project;

        return [
            'assignee_id' => [
                'required',
                'integer',
                Rule::exists('project_members', 'user_id')
                    ->where('project_id', $project->id)
                    ->whereNot('role', ProjectRole::Viewer->value),
            ],
        ];
    }
}
