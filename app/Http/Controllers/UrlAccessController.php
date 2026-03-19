<?php

namespace App\Http\Controllers;

use App\Models\Convention;
use Illuminate\Http\RedirectResponse;

class UrlAccessController extends Controller
{
    /**
     * Handle floor URL access: look up convention by floor token,
     * store URL session, and redirect to convention show page.
     */
    public function floor(string $token): RedirectResponse
    {
        $convention = Convention::where('floor_url_token', $token)->firstOrFail();

        session([
            'url_session' => [
                'convention_id' => $convention->id,
                'type' => 'floor',
                'token' => $token,
            ],
        ]);

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Handle section URL access: look up convention by section token,
     * store URL session, and redirect to convention show page.
     */
    public function section(string $token): RedirectResponse
    {
        $convention = Convention::where('section_url_token', $token)->firstOrFail();

        session([
            'url_session' => [
                'convention_id' => $convention->id,
                'type' => 'section',
                'token' => $token,
            ],
        ]);

        return redirect()->route('conventions.show', $convention);
    }
}
