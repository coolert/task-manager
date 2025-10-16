<?php

namespace App\Http\Requests;

use App\DTOs\LabelStoreDTO;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LabelStoreRequest extends FormRequest
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
            'name'  => ['required', 'string', 'max:40', Rule::unique('labels', 'name')->where('project_id', $project->id)],
            'color' => ['required', 'hex_color', 'max:16'],

        ];
    }

    public function toDTO(): LabelStoreDTO
    {
        /** @var Project $project */
        $project = $this->route('project');
        $data    = array_merge($this->validated(), ['project_id' => $project->id]);

        return LabelStoreDTO::fromArray($data);
    }
}
