<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAccessibility
{
    /**
     * Font size scales as a percentage of the browser default size, keyed by
     * the stored preference value. Must stay in sync with FONT_SIZE_SCALES in
     * resources/js/composables/useAccessibility.ts.
     *
     * @var array<string, string>
     */
    private const FONT_SIZE_PERCENTAGES = [
        's' => '90%',
        'm' => '100%',
        'l' => '110%',
        'xl' => '125%',
        'xxl' => '150%',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('fontSizePercent', $this->fontSizePercent($request->cookie('font_size')));
        View::share('dyslexicFont', $request->cookie('dyslexic_font') === '1');

        return $next($request);
    }

    /**
     * Resolve the stored font size preference to a CSS percentage, or null
     * when the browser default size should apply.
     */
    private function fontSizePercent(mixed $value): ?string
    {
        return is_string($value)
            ? self::FONT_SIZE_PERCENTAGES[$value] ?? null
            : null;
    }
}
