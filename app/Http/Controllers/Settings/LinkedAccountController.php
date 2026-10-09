<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LinkedAccountController extends Controller
{
    /**
     * Show the user's linked accounts settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/LinkedAccounts');
    }
}
