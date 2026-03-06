<?php

namespace App\Http\Requests;

use App\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreSectionRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Fields that may contain rich text content.
     *
     * @return array<int, string>
     */
    protected function richTextFields(): array
    {
        return ['information'];
    }

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
            'name' => ['required', 'string', 'max:255'],
            'number_of_seats' => ['required', 'integer', 'min:1'],
            'elder_friendly' => ['nullable', 'boolean'],
            'handicap_friendly' => ['nullable', 'boolean'],
            'information' => ['nullable', 'string'],
        ];
    }
}
