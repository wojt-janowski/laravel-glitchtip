<?php

declare(strict_types=1);

use WojtJanowski\GlitchTip\Data\Event;
use WojtJanowski\GlitchTip\Data\Issue;
use WojtJanowski\GlitchTip\Data\LogStats;
use WojtJanowski\GlitchTip\Data\Monitor;
use WojtJanowski\GlitchTip\Data\MonitorCheck;
use WojtJanowski\GlitchTip\Data\ProjectKey;
use WojtJanowski\GlitchTip\Enums\IssueStatus;
use WojtJanowski\GlitchTip\Enums\Level;
use WojtJanowski\GlitchTip\Enums\MonitorType;

describe('Issue', function (): void {
    it('maps an issue payload, casting the string count', function (): void {
        $issue = Issue::fromArray(issuePayload([
            'assignedTo' => ['type' => 'team', 'id' => '3', 'name' => 'Backend', 'slug' => 'backend'],
            'lastRelease' => ['version' => 'api@1.4.0', 'shortVersion' => '1.4.0', 'dateCreated' => '2026-10-19T00:00:00Z', 'dateReleased' => null],
        ]));

        expect($issue->count)->toBe(17)
            ->and($issue->status)->toBe(IssueStatus::Unresolved)
            ->and($issue->level)->toBe(Level::Error)
            ->and($issue->project->slug)->toBe('backend')
            ->and($issue->assignedTo?->isTeam())->toBeTrue()
            ->and($issue->lastRelease?->version)->toBe('api@1.4.0')
            ->and($issue->lastRelease?->dateReleased)->toBeNull()
            ->and($issue->lastSeen->toIso8601ZuluString())->toBe('2026-10-20T09:30:00Z');
    });

    it('leaves an unknown status as null instead of failing', function (): void {
        expect(Issue::fromArray(issuePayload(['status' => 'reprocessing']))->status)->toBeNull();
    });
});

describe('Event', function (): void {
    it('flattens tags and exposes entries by type', function (): void {
        $event = Event::fromArray([
            'id' => 'abc',
            'eventID' => 'abc',
            'groupID' => '42',
            'projectID' => 7,
            'title' => 'RuntimeException: Boom',
            'message' => '',
            'type' => 'error',
            'dateCreated' => '2026-10-20T09:30:00Z',
            'dateReceived' => '2026-10-20T09:30:01Z',
            'tags' => [['key' => 'environment', 'value' => 'production'], ['key' => 'release', 'value' => '1.4.0']],
            'entries' => [
                ['type' => 'exception', 'data' => ['values' => [['type' => 'RuntimeException']]]],
                ['type' => 'breadcrumbs', 'data' => ['values' => []]],
            ],
            'nextEventID' => null,
            'previousEventID' => 'xyz',
        ]);

        expect($event->issueId)->toBe('42')
            ->and($event->tags)->toBe(['environment' => 'production', 'release' => '1.4.0'])
            ->and($event->entry('exception'))->toBe(['values' => [['type' => 'RuntimeException']]])
            ->and($event->entry('request'))->toBeNull()
            ->and($event->previousEventId)->toBe('xyz');
    });
});

describe('ProjectKey', function (): void {
    it('exposes the public DSN', function (): void {
        $key = ProjectKey::fromArray([
            'id' => '0b6e5d0c-0000-4000-8000-000000000001',
            'public' => '0b6e5d0c000040008000000000000001',
            'projectID' => 7,
            'label' => 'Default',
            'dateCreated' => '2026-01-01T00:00:00Z',
            'dsn' => ['public' => 'https://key@glitchtip.test/7', 'security' => 'https://glitchtip.test/api/7/security/?glitchtip_key=key'],
        ]);

        expect($key->dsn)->toBe('https://key@glitchtip.test/7')
            ->and($key->dsnUrls)->toHaveKey('security');
    });
});

describe('Monitor', function (): void {
    it('maps the monitor type and recent checks', function (): void {
        $monitor = Monitor::fromArray([
            'id' => 5,
            'name' => 'Homepage',
            'monitorType' => 'GET',
            'url' => 'https://example.com',
            'isUp' => false,
            'created' => '2026-01-01T00:00:00Z',
            'checks' => [['startCheck' => '2026-10-20T09:30:00Z', 'isUp' => false, 'reason' => 2]],
        ]);

        expect($monitor->type)->toBe(MonitorType::Get)
            ->and($monitor->isUp)->toBeFalse()
            ->and($monitor->checks)->toHaveCount(1)
            ->and($monitor->checks[0]->reason)->toBe(2);
    });
});

describe('MonitorCheck', function (): void {
    it('reads the snake_case heartbeat response', function (): void {
        $check = MonitorCheck::fromArray(['start_check' => '2026-10-21T09:13:57.497Z', 'is_up' => true, 'reason' => null]);

        expect($check->isUp)->toBeTrue()
            ->and($check->startedAt->toIso8601ZuluString())->toBe('2026-10-21T09:13:57Z');
    });
});

describe('LogStats', function (): void {
    it('keys series by name', function (): void {
        $stats = LogStats::fromArray([
            'intervals' => ['2026-10-20T09:00:00Z', '2026-10-20T10:00:00Z'],
            'series' => [['name' => 'error', 'data' => [3, 1]], ['name' => 'info', 'data' => [40, 52]]],
        ]);

        expect($stats->intervals)->toHaveCount(2)
            ->and($stats->series)->toBe(['error' => [3, 1], 'info' => [40, 52]]);
    });
});
