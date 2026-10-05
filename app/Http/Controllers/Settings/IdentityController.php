<?php

namespace App\Http\Controllers\Settings;

use App\Enums\GradeLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\IdentityUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IdentityController extends Controller
{
    /**
     * Show the user's identity settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Identity', [
            'gradeLevels' => GradeLevel::options(),
        ]);
    }

    /**
     * Update the user's identification data.
     *
     * The member status is frozen at registration and never changes here.
     */
    public function update(IdentityUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Identity updated.')]);

        return to_route('identity.edit');
    }
}
