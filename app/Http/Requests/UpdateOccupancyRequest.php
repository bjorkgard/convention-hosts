<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOccupancyRequest extends FormRequest
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
            'occupancy' => ['nullable', 'integer', Rule::in([0, 10, 25, 50, 75, 100])],
            'available_seats' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Require at least one of occupancy or available_seats
            if (is_null($this->occupancy) && is_null($this->available_seats)) {
                $validator->errors()->add(
                    'occupancy',
                    'Either occupancy or available_seats must be provided.'
                );
            }
        });
    }
}
