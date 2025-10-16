<?php

namespace App\Http\Requests;

use App\DTOs\TaskStoreDTO;
use App\Enums\ProjectRole;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
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
        $project = $this->route('project');

        return [
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'priority'    => ['required', 'in:' . implode(',', array_column(TaskPriority::cases(), 'value'))],
            'assignee_id' => [
                'nullable',
                Rule::exists('project_members', 'user_id')
                    ->where('project_id', $project->id)
                    ->whereNot('role', ProjectRole::Viewer->value),
            ],
            'due_date' => ['nullable', 'date'],
            'order_no' => ['nullable', 'integer', 'between:1,9999'],
        ];
    }

    public function toDTO(): TaskStoreDTO
    {
        $project   = $this->route('project');
        $data      = array_merge($this->validated(), ['project_id' => $project->id]);
        $creatorId = $this->user()->id;

        return TaskStoreDTO::fromArray($data, $creatorId);
    }
}
