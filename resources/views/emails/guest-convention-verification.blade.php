@component('mail::message')
# {{ __('emails.guest_verification.greeting', ['name' => $userName]) }}

{{ __('emails.guest_verification.body', ['convention' => $conventionName]) }}

{{ __('emails.guest_verification.action_description') }}

@component('mail::button', ['url' => $verificationUrl])
{{ __('emails.guest_verification.button') }}
@endcomponent

{{ __('emails.guest_verification.expiry', ['date' => $expiresAt]) }}

{{ __('emails.guest_verification.thanks') }}<br>
{{ config('app.name') }}
@endcomponent
