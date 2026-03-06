<?php

namespace App\Http\Requests;

use App\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
        return [
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'elder_friendly' => ['nullable', 'boolean'],
            'handicap_friendly' => ['nullable', 'boolean'],
        ];
    }
}
