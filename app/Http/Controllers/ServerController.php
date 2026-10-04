<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use App\Hytale\HytaleData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    use SharesHytaleProps;

    public function __construct(
        private readonly HytaleData $hytale,
    ) {}

    /**
     * List the Hytale servers a member can join.
     */
    public function index(Request $request): Response
    {
        $feed = $this->hytale->forMember($this->hytaleId($request));

        return Inertia::render('Servers', $this->baseProps($request, [
            'servers' => $feed->servers,
            'whitelists' => $feed->whitelists,
            'error' => $feed->error,
        ]));
    }
}
