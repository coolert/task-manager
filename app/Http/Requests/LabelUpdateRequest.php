<?php

namespace App\Http\Requests;

use App\DTOs\LabelUpdateDTO;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelUpdateRequest extends FormRequest
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
        /** @var Label|null $label */
        $label = $this->route('label');

        return [
            'name' => ['sometimes', 'string', 'max:40',
                Rule::unique('labels', 'name')
                    ->where('project_id', $project->id)
                    ->ignore($label->id),
            ],
            'color' => ['sometimes', 'hex_color', 'max:16'],
        ];
    }

    public function toDTO(): LabelUpdateDTO
    {
        return LabelUpdateDTO::fromArray($this->all());
    }
}
