<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\HytaleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
    ) {}

    /**
     * Show the member's Hytale play sessions.
     */
    public function index(Request $request): Response
    {
        $feed = $this->hytale->forMember($this->hytaleId($request));

        return Inertia::render('Sessions', $this->baseProps($request, [
            'sessions' => $feed->sessions,
            'servers' => $feed->servers,
            'error' => $feed->error,
        ]));
    }
}
