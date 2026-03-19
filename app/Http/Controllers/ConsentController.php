<?php

namespace App\Http\Controllers;

use App\Actions\Consent\RecordUserConsentAction;
use App\Http\Requests\Consent\RecordConsentRequest;
use Illuminate\Http\RedirectResponse;

class ConsentController extends Controller
{
    public function __construct(
        private readonly RecordUserConsentAction $recordUserConsentAction,
    ) {}

    public function store(RecordConsentRequest $request): RedirectResponse
    {
        $state = $request->validated('state');

        if ($request->user()) {
            $this->recordUserConsentAction->execute($request->user(), $state);
        } else {
            $request->session()->put('consent_state', $state);
        }

        return back();
    }
}
