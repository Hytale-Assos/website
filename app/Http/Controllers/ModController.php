<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesHytaleProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModController extends Controller
{
    use SharesHytaleProps;

    /**
     * List the mods created by the club members.
     *
     * Placeholder for now: mods will be sourced from the club's Git
     * repositories later.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Mods', $this->baseProps($request));
    }
}
