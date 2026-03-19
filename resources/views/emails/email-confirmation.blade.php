@component('mail::message')
# {{ __('emails.confirmation.greeting', ['name' => $userName]) }}

{{ __('emails.confirmation.body') }}

@component('mail::button', ['url' => $confirmationUrl])
{{ __('emails.confirmation.button') }}
@endcomponent

{{ __('emails.confirmation.expiry', ['date' => $expiresAt]) }}

{{ __('emails.confirmation.no_action') }}

{{ __('emails.confirmation.thanks') }}<br>
{{ config('app.name') }}
@endcomponent
