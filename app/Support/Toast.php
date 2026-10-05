<?php

namespace App\Support;

use Inertia\Inertia;

/**
 * Flashes the toast notification consumed by resources/js/lib/flashToast.
 * The single definition of the wire contract: a type and a message, the
 * only keys the frontend understands.
 */
class Toast
{
    /**
     * Flash a success toast.
     */
    public static function success(string $message): void
    {
        static::flash('success', $message);
    }

    /**
     * Flash an error toast.
     */
    public static function error(string $message): void
    {
        static::flash('error', $message);
    }

    /**
     * @param  'success'|'error'  $type
     */
    private static function flash(string $type, string $message): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
