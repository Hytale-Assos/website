<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\Points\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointsController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly PointsService $points,
    ) {}

    /**
     * Show the member's "Points Open" earned from play time.
     */
    public function index(Request $request): Response
    {
        $result = $this->points->forMember($this->hytaleId($request));

        return Inertia::render('Points', $this->baseProps($request, [
            'points' => $result['points'],
            'error' => $result['error'],
        ]));
    }
}
