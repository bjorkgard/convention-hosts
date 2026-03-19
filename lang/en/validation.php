<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    |
    | Application-specific validation messages used in form requests.
    | Laravel's built-in validation messages are provided by the framework.
    |
    */

    'convention_overlapping' => 'A convention already exists in this location during these dates.',
    'occupancy_or_seats_required' => 'Either occupancy or available_seats must be provided.',
    'password_regex' => 'The password must contain at least one lowercase letter, one uppercase letter, one number, and one symbol (@$!%*#?&).',
    'jwpub_domain' => 'The email address cannot contain jwpub.org domain.',
    'locale_not_available' => 'The selected locale is not available.',
];
