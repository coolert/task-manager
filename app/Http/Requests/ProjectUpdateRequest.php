<?php

namespace App\Http\Requests;

use App\DTOs\ProjectUpdateDTO;
use Illuminate\Foundation\Http\FormRequest;

class ProjectUpdateRequest extends FormRequest
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
            'name'        => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function toDTO(): ProjectUpdateDTO
    {
        return ProjectUpdateDTO::fromArray($this->all());
    }
}
