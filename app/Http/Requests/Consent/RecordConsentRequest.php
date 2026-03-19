<?php

namespace App\Http\Requests\Consent;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow both authenticated users and URL-session (anonymous) users
        return $this->user() !== null || $this->session()->has('url_session');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'state' => [
                'required',
                'string',
                Rule::in([
                    User::CONSENT_STATE_ACCEPTED,
                    User::CONSENT_STATE_DECLINED,
                ]),
            ],
        ];
    }
}
