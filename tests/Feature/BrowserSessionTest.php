<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WojtJanowski\GlitchTip\Facades\GlitchTip;

describe('usingSession', function (): void {
    it('authenticates with the browser session cookies instead of the configured token', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::usingSession('https://monitoring.test', 'session-abc', 'csrf-xyz', 'acme')->issues()->list();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://monitoring.test/api/0/organizations/acme/issues/')
            && $request->header('Cookie') === ['sessionid=session-abc; csrftoken=csrf-xyz']
            && ! $request->hasHeader('Authorization'));
    });

    it('passes Django CSRF checks on writes from a server', function (): void {
        Http::fake(['*' => Http::response(issuePayload())]);

        GlitchTip::usingSession('https://monitoring.test:8443/sub/', 'session-abc', 'csrf-xyz', 'acme')
            ->comments()->create('42', 'Looking into it');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('X-CSRFToken', 'csrf-xyz')
            && $request->hasHeader('Origin', 'https://monitoring.test:8443'));
    });

    it('leaves the configured token client alone', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::usingSession('https://monitoring.test', 'session-abc', 'csrf-xyz', 'acme');
        GlitchTip::issues()->list();

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer test-token')
            && ! $request->hasHeader('Cookie'));
    });

    it('refuses an incomplete session', function (string $sessionId, string $csrfToken): void {
        GlitchTip::usingSession('https://monitoring.test', $sessionId, $csrfToken);
    })->with([
        'no session' => ['', 'csrf-xyz'],
        'no csrf token' => ['session-abc', ' '],
    ])->throws(InvalidArgumentException::class);
});

it('reads the signed-in user', function (): void {
    Http::fake(['*/api/0/users/me/' => Http::response([
        'id' => '1', 'email' => 'jane@example.test', 'name' => 'Jane', 'username' => 'jane', 'isSuperuser' => true,
        'options' => ['timezone' => 'Australia/Sydney'],
    ])]);

    $user = GlitchTip::usingSession('https://monitoring.test', 'session-abc', 'csrf-xyz')->users()->get();

    expect($user->id)->toBe('1')
        ->and($user->email)->toBe('jane@example.test')
        ->and($user->isSuperuser)->toBeTrue();
});
