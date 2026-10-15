<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'app' => [
                'timezone' => config('app.timezone'),
            ],
            'auth' => [
                'user' => $request->user()
                    ? $this->shareableUser($request->user())
                    : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The member data shared with every Inertia page: an explicit
     * allowlist of the fields the frontend consumes, so no column ever
     * leaks into page payloads by default. Internal identifiers,
     * timestamps and pending claims stay server-side; a new model
     * column has to be added here deliberately to reach the client.
     *
     * @return array<string, mixed>
     */
    private function shareableUser(User $user): array
    {
        return $user->only([
            'name',
            'email',
            'email_verified_at',
            'firstname',
            'lastname',
            'grade_level',
            'school_email',
            'is_internal',
            'is_public',
            'discord_id',
            'discord_nickname',
            'hytale_id',
            'hytale_nickname',
            'hytale_account_verified_at',
        ]);
    }
}
