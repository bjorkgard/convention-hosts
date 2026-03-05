@component('mail::message')
# Hello {{ $userName }},

You have been invited to join **{{ $conventionName }}**.

Please click the button below to set your password and activate your account.

@component('mail::button', ['url' => $invitationUrl])
Accept Invitation
@endcomponent

This invitation link will expire on **{{ $expiresAt }}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
