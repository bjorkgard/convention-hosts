@component('mail::message')
# Hello {{ $userName }},

Your convention **{{ $conventionName }}** has been created successfully.

Please click the button below to verify your email and set your password.

@component('mail::button', ['url' => $verificationUrl])
Verify Email & Set Password
@endcomponent

This verification link will expire on **{{ $expiresAt }}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
