@component('mail::message')
# Hello {{ $userName }},

Please confirm your email address by clicking the button below.

@component('mail::button', ['url' => $confirmationUrl])
Confirm Email Address
@endcomponent

This confirmation link will expire on **{{ $expiresAt }}**.

If you did not request this change, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
