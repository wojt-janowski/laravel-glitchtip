<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;
use WojtJanowski\GlitchTip\Data\MonitorInput;
use WojtJanowski\GlitchTip\Enums\IssueSort;
use WojtJanowski\GlitchTip\Exceptions\MissingConfigurationException;
use WojtJanowski\GlitchTip\Exceptions\RequestFailedException;
use WojtJanowski\GlitchTip\Facades\GlitchTip;

describe('authentication and scoping', function (): void {
    it('sends the bearer token to the configured instance', function (): void {
        Http::fake(['glitchtip.test/api/0/organizations/acme/' => Http::response([
            'id' => '1', 'slug' => 'acme', 'name' => 'Acme', 'dateCreated' => '2026-01-01T00:00:00Z',
        ])]);

        expect(GlitchTip::organizations()->get()->name)->toBe('Acme');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://glitchtip.test/api/0/organizations/acme/'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    });

    it('scopes calls to another organization on request', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::forOrganization('globex')->issues()->list();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://glitchtip.test/api/0/organizations/globex/issues/'));
    });

    it('fails clearly when no organization is configured', function (): void {
        config()->set('glitchtip.organization');
        app()->forgetInstance(WojtJanowski\GlitchTip\GlitchTip::class);

        GlitchTip::issues()->list();
    })->throws(MissingConfigurationException::class, 'organization');

    it('fails clearly when no token is configured', function (): void {
        config()->set('glitchtip.token');
        app()->forgetInstance(WojtJanowski\GlitchTip\GlitchTip::class);

        GlitchTip::issues()->get(42);
    })->throws(MissingConfigurationException::class, 'token');
});

describe('issues', function (): void {
    it('filters the list and follows the cursor across pages', function (): void {
        Http::fake([
            'glitchtip.test/api/0/organizations/acme/issues/?*cursor=page2*' => Http::response([issuePayload(['id' => '43'])]),
            'glitchtip.test/api/0/organizations/acme/issues/*' => Http::response([issuePayload()], 200, [
                'Link' => '<https://glitchtip.test/api/0/organizations/acme/issues/>; rel="previous"; results="false", '
                    .'<https://glitchtip.test/api/0/organizations/acme/issues/?cursor=page2>; rel="next"; results="true"; cursor="page2"',
                'X-Hits' => '2',
            ]),
        ]);

        $page = GlitchTip::issues()->list(query: 'is:unresolved', projects: [7, 9], sort: IssueSort::LastSeenDesc);

        expect($page->hits)->toBe(2)
            ->and($page->hasMore())->toBeTrue()
            ->and($page->lazy()->map(fn ($issue) => $issue->id)->all())->toBe(['42', '43']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://glitchtip.test/api/0/organizations/acme/issues/?query=is%3Aunresolved&project=7&project=9&sort=-last_seen');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'cursor=page2')
            && str_contains($request->url(), 'project=7&project=9'));
    });

    it('sends a partial update and maps the result', function (): void {
        Http::fake(['glitchtip.test/api/0/issues/42/' => Http::response(issuePayload(['status' => 'resolved', 'userReportCount' => 0]))]);

        $issue = GlitchTip::issues()->update(42, IssueUpdate::resolved()->with(IssueUpdate::unassigned()));

        expect($issue->isResolved())->toBeTrue();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->body() === '{"status":"resolved","assignedTo":null}');
    });

    it('scopes bulk updates to the filter', function (): void {
        Http::fake(['*' => Http::response(['status' => 'ignored'])]);

        GlitchTip::issues()->bulkUpdate(IssueFilter::ids([1, 2]), IssueUpdate::ignored());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://glitchtip.test/api/0/organizations/acme/issues/?id=1&id=2'
            && $request['status'] === 'ignored');
    });

    it('resolves a short id to its issue', function (): void {
        Http::fake(['glitchtip.test/api/0/organizations/acme/shortids/BACKEND-1A/' => Http::response([
            'organizationSlug' => 'acme', 'projectSlug' => 'backend', 'groupId' => '42', 'shortId' => 'BACKEND-1A',
            'group' => issuePayload(),
        ])]);

        expect(GlitchTip::issues()->findByShortId('BACKEND-1A')->id)->toBe('42');
    });
});

describe('releases', function (): void {
    it('encodes versions that contain slashes and @', function (): void {
        Http::fake(['*' => Http::response([])]);

        GlitchTip::releases()->deploys('api@feature/x');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://glitchtip.test/api/0/organizations/acme/releases/api%40feature%2Fx/deploys/');
    });

    it('omits unset fields when creating', function (): void {
        Http::fake(['*' => Http::response([
            'version' => '1.4.0', 'shortVersion' => '1.4.0', 'dateCreated' => '2026-10-20T00:00:00Z', 'dateReleased' => null, 'projects' => [['name' => 'Backend', 'slug' => 'backend']],
        ], 201)]);

        $release = GlitchTip::releases()->create('1.4.0', ['backend']);

        expect($release->projects)->toBe(['backend']);

        Http::assertSent(fn (Request $request): bool => $request->body() === '{"version":"1.4.0","projects":["backend"]}');
    });
});

describe('errors and retries', function (): void {
    it('raises the GlitchTip detail message', function (): void {
        Http::fake(['*' => Http::response(['detail' => 'Not Found'], 404)]);

        try {
            GlitchTip::issues()->get(999);
            $this->fail('Expected an exception.');
        } catch (RequestFailedException $exception) {
            expect($exception->isNotFound())->toBeTrue()
                ->and($exception->getMessage())->toBe('GlitchTip GET /issues/999/ failed with 404: Not Found');
        }
    });

    it('summarises validation errors', function (): void {
        Http::fake(['*' => Http::response(['detail' => [['loc' => ['body', 'name'], 'msg' => 'Field required', 'type' => 'missing']]], 422)]);

        GlitchTip::monitors()->create(new MonitorInput(name: ''));
    })->throws(RequestFailedException::class, 'Field required');

    it('retries idempotent requests on a gateway error', function (): void {
        Http::fake(['*' => Http::sequence()->push([], 503)->push(issuePayload())]);

        expect(GlitchTip::issues()->get(42)->id)->toBe('42');

        Http::assertSentCount(2);
    });

    it('never retries a POST', function (): void {
        Http::fake(['*' => Http::sequence()->push([], 503)->push([], 201)]);

        expect(fn () => GlitchTip::comments()->create(42, 'Looking into it'))->toThrow(RequestFailedException::class);

        Http::assertSentCount(1);
    });
});
