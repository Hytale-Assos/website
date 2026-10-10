<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    use SharesHytaleProps;

    /**
     * Live map of a server (BlueMap / Dynmap style), coming later.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Maps', $this->baseProps($request));
    }
}
