<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\PersonalDataInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Download the user's personal data as JSON.
     */
    public function export(Request $request, PersonalDataInventory $inventory): StreamedResponse
    {
        $user = $request->user();

        return response()->streamDownload(
            function () use ($inventory, $user): void {
                echo json_encode(
                    [
                        'generated_at' => now()->toIso8601String(),
                        'sections' => $inventory->sections($user),
                    ],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                );
            },
            'personal-data.json',
            ['Content-Type' => 'application/json'],
        );
    }
}
