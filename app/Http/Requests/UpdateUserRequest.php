<?php

namespace App\Http\Requests;

use App\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use SanitizesInput;

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
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                function ($attribute, $value, $fail) {
                    if (stripos($value, 'jwpub.org') !== false) {
                        $fail('The email address cannot contain jwpub.org domain.');
                    }
                },
            ],
            'mobile' => ['required', 'string', 'max:255'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'])],
            'floor_ids' => ['nullable', 'array'],
            'floor_ids.*' => ['uuid', 'exists:floors,id'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['uuid', 'exists:sections,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Require floor_ids if FloorUser role is assigned
            if (in_array('FloorUser', $this->roles ?? []) && empty($this->floor_ids)) {
                $validator->errors()->add(
                    'floor_ids',
                    'Floor IDs are required when FloorUser role is assigned.'
                );
            }

            // Require section_ids if SectionUser role is assigned
            if (in_array('SectionUser', $this->roles ?? []) && empty($this->section_ids)) {
                $validator->errors()->add(
                    'section_ids',
                    'Section IDs are required when SectionUser role is assigned.'
                );
            }
        });
    }
}
