<?php

use Illuminate\Testing\TestResponse;

/**
 * Extract the opening tag of the root <html> element from the raw
 * server-rendered response content.
 */
function accessibilityRootHtmlTag(TestResponse $response): string
{
    preg_match('/<html\b[^>]*>/i', (string) $response->getContent(), $matches);

    return $matches[0] ?? '';
}

/**
 * Extract the inline style declaration of the root <html> element,
 * or an empty string when the element carries no style attribute.
 */
function accessibilityRootHtmlStyle(TestResponse $response): string
{
    preg_match('/\bstyle=(["\'])(.*?)\1/i', accessibilityRootHtmlTag($response), $matches);

    return $matches[2] ?? '';
}

/**
 * Extract the CSS classes of the root <html> element as a list of tokens.
 */
function accessibilityRootHtmlClasses(TestResponse $response): array
{
    preg_match('/\bclass=(["\'])(.*?)\1/i', accessibilityRootHtmlTag($response), $matches);

    if (! isset($matches[2]) || trim($matches[2]) === '') {
        return [];
    }

    return preg_split('/\s+/', trim($matches[2]));
}

test('login page applies the font size cookie as an inline font size style on the root html element', function (string $fontSize, string $expectedSize) {
    $response = $this->withUnencryptedCookie('font_size', $fontSize)->get('/login');

    $style = accessibilityRootHtmlStyle($response);

    expect($style)->toMatch("/font-size\s*:\s*{$expectedSize}/i");
})->with([
    's' => ['s', '90%'],
    'm' => ['m', '100%'],
    'l' => ['l', '110%'],
    'xl' => ['xl', '125%'],
    'xxl' => ['xxl', '150%'],
]);

test('login page omits any inline font size style when the font size cookie is absent or system', function (?string $fontSize) {
    $response = $fontSize === null
        ? $this->get('/login')
        : $this->withUnencryptedCookie('font_size', $fontSize)->get('/login');

    expect(accessibilityRootHtmlTag($response))->not->toBe('');
    expect(accessibilityRootHtmlStyle($response))->not->toMatch('/font-size/i');
})->with([
    'absent' => [null],
    'system' => ['system'],
]);

test('login page omits any inline font size style and leaks nothing for invalid font size cookie values', function (string $fontSize) {
    $response = $this->withUnencryptedCookie('font_size', $fontSize)->get('/login');

    $htmlTag = accessibilityRootHtmlTag($response);

    expect($htmlTag)->not->toBe('');
    expect(accessibilityRootHtmlStyle($response))->not->toMatch('/font-size/i');
    expect($htmlTag)->not->toContain($fontSize);
})->with([
    'unknown value' => ['unknown'],
    'uppercase value' => ['S'],
    'numeric value' => ['90%'],
    'hostile value' => ['"><script>alert(1)</script>'],
]);

test('login page adds the font-dyslexic class to the root html element when the dyslexic font cookie is enabled', function () {
    $response = $this->withUnencryptedCookie('dyslexic_font', '1')->get('/login');

    expect(accessibilityRootHtmlClasses($response))->toContain('font-dyslexic');
});

test('login page omits the font-dyslexic class when the dyslexic font cookie is disabled, absent, or invalid', function (?string $dyslexicFont) {
    $response = $dyslexicFont === null
        ? $this->get('/login')
        : $this->withUnencryptedCookie('dyslexic_font', $dyslexicFont)->get('/login');

    expect(accessibilityRootHtmlTag($response))->not->toBe('');
    expect(accessibilityRootHtmlClasses($response))->not->toContain('font-dyslexic');
})->with([
    'disabled' => ['0'],
    'absent' => [null],
    'other value' => ['2'],
    'non-numeric value' => ['true'],
]);

test('login page applies both accessibility preferences together for guest requests', function () {
    $response = $this
        ->withUnencryptedCookie('font_size', 'l')
        ->withUnencryptedCookie('dyslexic_font', '1')
        ->get('/login');

    expect(accessibilityRootHtmlStyle($response))->toMatch('/font-size\s*:\s*110%/i');
    expect(accessibilityRootHtmlClasses($response))->toContain('font-dyslexic');
});
