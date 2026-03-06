<?php

namespace App\Http\Requests;

use App\Concerns\SanitizesInput;
use App\Models\Convention;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConventionRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Fields that may contain rich text content.
     *
     * @return array<int, string>
     */
    protected function richTextFields(): array
    {
        return ['other_info'];
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
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'other_info' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only check for overlapping conventions if all required location/date fields are present
            if ($this->city && $this->country && $this->start_date && $this->end_date && $this->hasOverlappingConvention()) {
                $validator->errors()->add(
                    'start_date',
                    'A convention already exists in this location during these dates.'
                );
            }
        });
    }

    /**
     * Check if there's an overlapping convention in the same location.
     * Excludes the current convention being updated.
     */
    private function hasOverlappingConvention(): bool
    {
        $conventionId = $this->route('convention')?->id ?? $this->route('convention');

        return Convention::where('city', $this->city)
            ->where('country', $this->country)
            ->where('id', '!=', $conventionId)
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            })
            ->exists();
    }
}
