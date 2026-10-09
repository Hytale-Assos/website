<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\HytaleData;
use App\Hytale\Points\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
        private readonly PointsService $points,
    ) {}

    /**
     * Overview of the member's Hytale activity.
     */
    public function index(Request $request): Response
    {
        $hytaleId = $this->hytaleId($request);

        $feed = $this->hytale->forMember($hytaleId);
        $points = $this->points->forMember($hytaleId);

        return Inertia::render('Dashboard', $this->baseProps($request, [
            'counts' => [
                'points' => (float) ($points['points']['points'] ?? 0),
                'servers' => count($feed->servers),
                'sessions' => count($feed->sessions),
            ],
            'recentSessions' => array_slice($feed->sessions, 0, 3),
            'servers' => $feed->servers,
            'error' => $feed->error ?? $points['error'],
        ]));
    }
}
