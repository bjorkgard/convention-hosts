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
        $this->recordUserConsentAction->execute(
            $request->user(),
            $request->validated('state'),
        );

        return back();
    }
}
