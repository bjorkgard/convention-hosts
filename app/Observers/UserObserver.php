<?php

namespace App\Observers;

use App\Mail\EmailConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class UserObserver
{
    /**
     * Handle the User "updating" event.
     *
     * When the email attribute is dirty on an existing user,
     * set email_confirmed to false before saving.
     */
    public function updating(User $user): void
    {
        if ($user->isDirty('email') && $user->exists) {
            $user->email_confirmed = false;
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * When the email was changed on an existing user,
     * generate a signed confirmation URL and send the confirmation email.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('email')) {
            $confirmationUrl = URL::temporarySignedRoute(
                'email.confirm',
                now()->addHours(24),
                ['user' => $user->id]
            );

            Mail::to($user->email)->send(
                new EmailConfirmation($user, $confirmationUrl)
            );
        }
    }
}
