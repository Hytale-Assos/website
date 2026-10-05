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
     */
    public function update(IdentityUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'grade_level' => $validated['grade_level'],
            'is_internal' => $validated['status'] === 'internal',
            'is_external' => $validated['status'] === 'external',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Identity updated.')]);

        return to_route('identity.edit');
    }
}
