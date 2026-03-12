<?php

namespace App\Actions\Consent;

use App\Models\User;
use InvalidArgumentException;

class RecordUserConsentAction
{
    public function execute(User $user, string $state): User
    {
        if (! in_array($state, [User::CONSENT_STATE_ACCEPTED, User::CONSENT_STATE_DECLINED], true)) {
            throw new InvalidArgumentException('Consent state must be accepted or declined.');
        }

        $currentPolicyVersion = (int) config('consent.current_policy_version');
        $timestamp = now();

        $shouldPreserveDecidedAt = in_array($user->consent_state, [User::CONSENT_STATE_ACCEPTED, User::CONSENT_STATE_DECLINED], true)
            && $user->consent_version === $currentPolicyVersion
            && $user->consent_decided_at !== null;

        $user->forceFill([
            'consent_state' => $state,
            'consent_version' => $currentPolicyVersion,
            'consent_decided_at' => $shouldPreserveDecidedAt ? $user->consent_decided_at : $timestamp,
            'consent_updated_at' => $timestamp,
        ])->save();

        return $user->refresh();
    }
}
