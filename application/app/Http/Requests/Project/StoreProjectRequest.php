<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:128'],
            'geo_blocked_countries' => ['nullable', 'string', 'min:2', 'max:128'],
            'regenerate_api_keys' => ['nullable', 'in:yes'],
            'session_valid_for_seconds' => ['required', 'integer', 'min:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'regenerate_api_keys.in' => 'This value is invalid.',
        ];
    }
}
