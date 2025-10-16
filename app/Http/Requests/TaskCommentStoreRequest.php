<?php

namespace App\Http\Requests;

use App\DTOs\TaskCommentStoreDTO;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class TaskCommentStoreRequest extends FormRequest
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
            'content' => ['required', 'string'],
        ];
    }

    public function toDTO(): TaskCommentStoreDTO
    {
        /** @var Task|null $task */
        $task = $this->route('task');
        /** @var User|null $user */
        $user = $this->user();

        return TaskCommentStoreDTO::fromArray($this->validated(), $task, $user);
    }
}
