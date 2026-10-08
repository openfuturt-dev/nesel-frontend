<?php

namespace App\Http\Controllers;

use App\Actions\SendContactRequestNotification;
use App\Http\Requests\StoreContactRequest;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;

use function Illuminate\Support\defer;

class ContactRequestController extends Controller
{
    public function store(StoreContactRequest $request, SendContactRequestNotification $sendNotification): RedirectResponse
    {
        // Stored before anything else can fail. A resubmitted token (double click,
        // refresh, concurrent request) returns the existing row instead of a new one.
        $contactRequest = ContactRequest::createOrFirst(
            ['submission_token' => $request->submissionToken()],
            [
                ...$request->safe()->except('submission_token'),
                ...$request->attribution(),
            ],
        );

        // First attempt after the response is sent, so the visitor never waits
        // for SMTP. The scheduled contact-requests:dispatch command retries
        // anything this attempt misses, including when it never runs.
        if ($contactRequest->wasRecentlyCreated) {
            defer(fn () => $sendNotification->handle($contactRequest));
        }

        return to_route('home')
            ->withFragment('contact')
            ->with('contact_success', 'Merci ! Votre demande a bien été envoyée. Un conseiller Nesel vous recontactera rapidement.');
    }
}
