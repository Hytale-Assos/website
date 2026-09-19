<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\HytaleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
    ) {}

    /**
     * Overview of the member's Hytale activity.
     */
    public function index(Request $request): Response
    {
        $feed = $this->hytale->forMember($this->hytaleId($request));

        return Inertia::render('Dashboard', $this->baseProps($request, [
            'counts' => [
                'servers' => count($feed->servers),
                'whitelists' => count($feed->whitelists),
                'sessions' => count($feed->sessions),
            ],
            'recentSessions' => array_slice($feed->sessions, 0, 3),
            'servers' => $feed->servers,
            'error' => $feed->error,
        ]));
    }
}
