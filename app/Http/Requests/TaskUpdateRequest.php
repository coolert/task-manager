<?php

namespace App\Http\Requests;

use App\DTOs\TaskUpdateDTO;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'title'       => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
            'priority'    => ['sometimes', 'in:' . implode(',', array_column(TaskPriority::cases(), 'value'))],
            'assignee_id' => ['prohibited'],
            'due_date'    => ['sometimes', 'nullable', 'date'],
            'order_no'    => ['sometimes', 'nullable', 'integer', 'between:1,9999'],
        ];
    }

    public function toDTO(): TaskUpdateDTO
    {
        return TaskUpdateDTO::fromArray($this->all());
    }
}
