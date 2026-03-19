<?php

return [
    'invitation' => [
        'subject' => 'Invitation to :convention',
        'greeting' => 'Hello :name,',
        'body' => 'You have been invited to join **:convention**.',
        'action_description' => 'Please click the button below to set your password and activate your account.',
        'button' => 'Accept Invitation',
        'expiry' => 'This invitation link will expire on **:date**.',
        'thanks' => 'Thanks,',
    ],
    'confirmation' => [
        'subject' => 'Confirm Your Email Address',
        'greeting' => 'Hello :name,',
        'body' => 'Please confirm your email address by clicking the button below.',
        'button' => 'Confirm Email Address',
        'expiry' => 'This confirmation link will expire on **:date**.',
        'no_action' => 'If you did not request this change, no further action is required.',
        'thanks' => 'Thanks,',
    ],
    'guest_verification' => [
        'subject' => 'Verify your email for :convention',
        'greeting' => 'Hello :name,',
        'body' => 'Your convention **:convention** has been created successfully.',
        'action_description' => 'Please click the button below to verify your email and set your password.',
        'button' => 'Verify Email & Set Password',
        'expiry' => 'This verification link will expire on **:date**.',
        'thanks' => 'Thanks,',
    ],
];
