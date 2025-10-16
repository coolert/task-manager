<?php

namespace App\Http\Requests;

use App\DTOs\TaskUpdateDTO;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
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
            'title'       => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'priority'    => ['sometimes', 'in:' . implode(',', array_column(TaskPriority::cases(), 'value'))],
            'assignee_id' => [
                'sometimes',
                'nullable',
                Rule::exists('project_members', 'user_id')
                    ->where('project_id', $project->id)
                    ->whereNot('role', ProjectRole::Viewer->value),
            ],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'order_no' => ['sometimes', 'nullable', 'integer', 'between:1,9999'],
        ];
    }

    public function toDTO(): TaskUpdateDTO
    {
        return TaskUpdateDTO::fromArray($this->all());
    }
}
