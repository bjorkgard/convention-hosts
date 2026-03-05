<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetPasswordRequest;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Display the password creation form for an invited user.
     *
     * The route uses signed middleware to verify the URL signature.
     */
    public function show(Request $request, User $user, Convention $convention): Response
    {
        return Inertia::render('auth/invitation', [
            'user' => $user->only('id', 'first_name', 'last_name', 'email'),
            'convention' => $convention->only('id', 'name'),
        ]);
    }

    /**
     * Set the user's password and confirm their email.
     */
    public function store(SetPasswordRequest $request, User $user, Convention $convention): RedirectResponse
    {
        $user->update([
            'password' => $request->validated('password'),
            'email_confirmed' => true,
        ]);

        return redirect()->route('login');
    }
}
