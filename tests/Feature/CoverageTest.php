<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WojtJanowski\GlitchTip\GlitchTip;

/**
 * @return array<string, array{method: string, path: string}>
 */
function specOperations(): array
{
    /** @var array{paths: array<string, array<string, array{operationId: string}>>} $spec */
    $spec = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/openapi.json'), true, flags: JSON_THROW_ON_ERROR);
    $operations = [];

    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $operation) {
            $operations[$operation['operationId']] = ['method' => strtoupper($method), 'path' => $path];
        }
    }

    return $operations;
}

/**
 * @return array<string, Closure|array{excluded?: string, alias?: string}>
 */
function coverageManifest(): array
{
    return require __DIR__.'/../Fixtures/coverage.php';
}

it('accounts for every operation in the GlitchTip API', function (): void {
    $operations = array_keys(specOperations());
    $manifest = array_keys(coverageManifest());

    expect(array_values(array_diff($operations, $manifest)))->toBe([], 'Operations in the spec with no client method, alias or exclusion')
        ->and(array_values(array_diff($manifest, $operations)))->toBe([], 'Manifest entries for operations no longer in the spec');
});

it('gives every exclusion a reason and every alias a covered target', function (): void {
    $manifest = coverageManifest();

    foreach ($manifest as $operation => $entry) {
        if (! is_array($entry)) {
            continue;
        }

        if (isset($entry['alias'])) {
            expect($manifest[$entry['alias']] ?? null)->toBeInstanceOf(Closure::class, "{$operation} aliases an operation without a client method");
        } else {
            expect($entry['excluded'] ?? '')->not->toBe('', "{$operation} is excluded without a reason");
        }
    }
});

it('calls each covered operation with its method and path', function (string $operation, Closure $call): void {
    ['method' => $method, 'path' => $path] = specOperations()[$operation];
    $pattern = '#^'.preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', preg_quote($path, '#')).'$#';

    Http::fake(['*' => Http::response([])]);

    $call(app(GlitchTip::class));

    Http::assertSent(fn (Request $request): bool => $request->method() === $method
        && preg_match($pattern, (string) parse_url($request->url(), PHP_URL_PATH)) === 1);
})->with(function (): iterable {
    foreach (coverageManifest() as $operation => $entry) {
        if ($entry instanceof Closure) {
            yield $operation => [$operation, $entry];
        }
    }
});
