<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Toast;
use Illuminate\Http\Request;

trait SharesHytaleProps
{
    /**
     * Resolve the current member's Hytale id.
     */
    protected function hytaleId(Request $request): ?string
    {
        $id = $request->user()?->hytale_id;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Props every member page shares: linked account, mock flag, API error.
     *
     * When the core is unreachable, a toast is flashed so the failure is made
     * obvious even on pages that render no inline alert.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    protected function baseProps(Request $request, array $props = []): array
    {
        $error = $props['error'] ?? null;

        if (is_string($error) && $error !== '') {
            Toast::error($error);
        }

        return [
            'hytaleId' => $this->hytaleId($request),
            'mock' => (bool) config('hytale.mock'),
            ...$props,
        ];
    }
}
