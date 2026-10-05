<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\PersonalDataInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataPrivacyController extends Controller
{
    /**
     * Show the user's personal data inventory.
     */
    public function edit(Request $request, PersonalDataInventory $inventory): Response
    {
        return Inertia::render('settings/Data', [
            'sections' => $inventory->sections($request->user()),
        ]);
    }
}
