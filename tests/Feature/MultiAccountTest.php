<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WojtJanowski\GlitchTip\Exceptions\MissingConfigurationException;
use WojtJanowski\GlitchTip\Facades\GlitchTip;
use WojtJanowski\GlitchTip\GlitchTip as Client;

function sentTo(string $host, string $token): Closure
{
    return fn (Request $request): bool => str_starts_with($request->url(), "https://{$host}/api/0/")
        && $request->hasHeader('Authorization', "Bearer {$token}");
}

describe('using', function (): void {
    it('returns a separate client for another account', function (): void {
        $other = GlitchTip::using('https://other.test', 'other-token', 'globex');

        expect($other)->not->toBe(app(Client::class))
            ->and($other->url())->toBe('https://other.test')
            ->and($other->organizationSlug())->toBe('globex')
            ->and(GlitchTip::url())->toBe('https://glitchtip.test')
            ->and(GlitchTip::organizationSlug())->toBe('acme')
            ->and(config('glitchtip.token'))->toBe('test-token');
    });

    it('keeps each account on its own server and token when calls interleave', function (): void {
        Http::fake(['*' => Http::response([])]);

        $first = GlitchTip::using('https://one.test', 'token-one', 'one');
        $second = GlitchTip::using('https://two.test/', 'token-two', 'two');

        $first->issues()->list();
        $second->issues()->list();
        GlitchTip::issues()->list();
        $first->monitors()->list();

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request): bool => sentTo('one.test', 'token-one')($request) && str_contains($request->url(), '/organizations/one/issues/'));
        Http::assertSent(fn (Request $request): bool => sentTo('two.test', 'token-two')($request) && str_contains($request->url(), '/organizations/two/issues/'));
        Http::assertSent(fn (Request $request): bool => sentTo('glitchtip.test', 'test-token')($request) && str_contains($request->url(), '/organizations/acme/issues/'));
        Http::assertSent(fn (Request $request): bool => sentTo('one.test', 'token-one')($request) && str_contains($request->url(), '/organizations/one/monitors/'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'two.test') && ! $request->hasHeader('Authorization', 'Bearer token-two'));
    });

    it('follows later pages on the account that fetched the first one', function (): void {
        Http::fake([
            'two.test/api/0/organizations/two/issues/?cursor=next' => Http::response([issuePayload(['id' => '2'])]),
            'two.test/*' => Http::response([issuePayload(['id' => '1'])], 200, [
                'Link' => '<https://two.test/api/0/organizations/two/issues/?cursor=next>; rel="next"; results="true"; cursor="next"',
            ]),
        ]);

        $page = GlitchTip::using('https://two.test', 'token-two', 'two')->issues()->list();

        // Touch the default account between pages; it must not leak into the cursor fetch.
        expect(GlitchTip::url())->toBe('https://glitchtip.test')
            ->and($page->lazy()->map(fn ($issue) => $issue->id)->all())->toBe(['1', '2']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => sentTo('two.test', 'token-two')($request) && str_contains($request->url(), 'cursor=next'));
    });

    it('does not carry the default organization over to another account', function (): void {
        GlitchTip::using('https://other.test', 'other-token')->issues()->list();
    })->throws(MissingConfigurationException::class, 'organization');

    it('keeps the account when re-scoped to another organization', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::using('https://other.test', 'other-token', 'globex')->forOrganization('initech')->teams()->list();

        Http::assertSent(fn (Request $request): bool => sentTo('other.test', 'other-token')($request) && str_contains($request->url(), '/organizations/initech/teams/'));
    });

    it('rejects an account without a URL or token', function (string $url, string $token): void {
        GlitchTip::using($url, $token);
    })->with([
        'no url' => ['', 'token'],
        'no token' => ['https://other.test', ' '],
    ])->throws(InvalidArgumentException::class);

    it('works without any default account configured', function (): void {
        config()->set('glitchtip.token');
        config()->set('glitchtip.organization');
        app()->forgetInstance(Client::class);
        Http::fake(['*' => Http::response([])]);

        GlitchTip::using('https://other.test', 'other-token', 'globex')->projects()->list();

        Http::assertSent(sentTo('other.test', 'other-token'));
    });
});
