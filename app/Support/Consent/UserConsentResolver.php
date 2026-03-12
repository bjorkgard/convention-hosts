<?php

namespace App\Support\Consent;

use App\Models\User;

class UserConsentResolver
{
    /**
     * @return array{
     *     state: string,
     *     version: int,
     *     allowOptionalStorage: bool,
     *     decidedAt: string|null,
     *     updatedAt: string|null
     * }
     */
    public function resolve(?User $user): array
    {
        $currentPolicyVersion = (int) config('consent.current_policy_version');

        if (! $user instanceof User) {
            return $this->undecidedContract($currentPolicyVersion);
        }

        $hasValidState = in_array($user->consent_state, [User::CONSENT_STATE_ACCEPTED, User::CONSENT_STATE_DECLINED], true);
        $hasCurrentVersion = $user->consent_version === $currentPolicyVersion;

        if (! $hasValidState || ! $hasCurrentVersion) {
            return $this->undecidedContract($currentPolicyVersion);
        }

        return [
            'state' => $user->consent_state,
            'version' => $currentPolicyVersion,
            'allowOptionalStorage' => $user->consent_state === User::CONSENT_STATE_ACCEPTED,
            'decidedAt' => $user->consent_decided_at?->toJSON(),
            'updatedAt' => $user->consent_updated_at?->toJSON(),
        ];
    }

    /**
     * @return array{
     *     state: string,
     *     version: int,
     *     allowOptionalStorage: bool,
     *     decidedAt: null,
     *     updatedAt: null
     * }
     */
    protected function undecidedContract(int $currentPolicyVersion): array
    {
        return [
            'state' => User::CONSENT_STATE_UNDECIDED,
            'version' => $currentPolicyVersion,
            'allowOptionalStorage' => false,
            'decidedAt' => null,
            'updatedAt' => null,
        ];
    }
}
