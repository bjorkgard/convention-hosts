@component('mail::message')
# {{ __('emails.invitation.greeting', ['name' => $userName]) }}

{{ __('emails.invitation.body', ['convention' => $conventionName]) }}

{{ __('emails.invitation.action_description') }}

@component('mail::button', ['url' => $invitationUrl])
{{ __('emails.invitation.button') }}
@endcomponent

{{ __('emails.invitation.expiry', ['date' => $expiresAt]) }}

{{ __('emails.invitation.thanks') }}<br>
{{ config('app.name') }}
@endcomponent
