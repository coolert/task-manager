<?php

namespace App\Http\Requests;

use App\DTOs\ProjectStoreDTO;
use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): ProjectStoreDTO
    {
        return ProjectStoreDTO::fromArray($this->validated(), $this->user()->id);
    }
}
