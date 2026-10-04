<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\Contracts\HytaleApiClient;
use App\Hytale\Exceptions\HytaleApiException;
use App\Hytale\HytaleData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WhitelistController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
        private readonly HytaleApiClient $client,
    ) {}

    /**
     * Show the servers the member is whitelisted on.
     */
    public function index(Request $request): Response
    {
        $feed = $this->hytale->forMember($this->hytaleId($request));

        return Inertia::render('Whitelist', $this->baseProps($request, [
            'whitelists' => $feed->whitelists,
            'servers' => $feed->servers,
            'error' => $feed->error,
        ]));
    }

    /**
     * Add the current member to a server whitelist.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hytale_server_id' => ['required', 'string'],
        ]);

        $hytaleId = $this->hytaleId($request);

        if ($hytaleId === null) {
            return $this->fail(__('Connect your Hytale account first.'));
        }

        try {
            $this->client->addToWhitelist($validated['hytale_server_id'], $hytaleId);
        } catch (HytaleApiException $exception) {
            return $this->fail($exception->getMessage());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added to whitelist.')]);

        return back();
    }

    /**
     * Remove a whitelist entry.
     */
    public function destroy(Request $request, string $entry): RedirectResponse
    {
        try {
            $this->client->removeFromWhitelist($entry);
        } catch (HytaleApiException $exception) {
            return $this->fail($exception->getMessage());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Removed from whitelist.')]);

        return back();
    }

    /**
     * Report a whitelist action failure as both an inline error and a toast,
     * so an unreachable core never results in a blank page.
     */
    private function fail(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back()->withErrors(['whitelist' => $message]);
    }
}
