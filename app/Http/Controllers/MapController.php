<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\HytaleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
    ) {}

    /**
     * Live map of a server (BlueMap / Dynmap style), coming later.
     *
     * Servers are exposed so the member can pick one to view.
     */
    public function index(Request $request): Response
    {
        $feed = $this->hytale->forMember($this->hytaleId($request));

        return Inertia::render('Maps', $this->baseProps($request, [
            'servers' => $feed->servers,
            'error' => $feed->error,
        ]));
    }
}
