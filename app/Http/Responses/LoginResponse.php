<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $conventions = $user->conventions()->pluck('conventions.id');

        $redirectTo = $conventions->count() === 1
            ? route('conventions.show', $conventions->first())
            : config('fortify.home', '/conventions');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($redirectTo);
    }
}
