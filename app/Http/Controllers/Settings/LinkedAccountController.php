<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LinkedAccountUpdateRequest;
use Illuminate\Http\RedirectResponse;
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

    /**
     * Update the user's linked accounts information.
     */
    public function update(LinkedAccountUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Linked accounts updated.')]);

        return to_route('accounts.edit');
    }
}
