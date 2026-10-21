<?php

declare(strict_types=1);

use WojtJanowski\GlitchTip\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature', 'Integration');

/**
 * @return array<string, mixed>
 */
function issuePayload(array $overrides = []): array
{
    return array_replace([
        'id' => '42',
        'shortId' => 'BACKEND-1A',
        'title' => 'RuntimeException: Boom',
        'culprit' => 'App\\Jobs\\SyncOrders::handle',
        'type' => 'error',
        'level' => 'error',
        'status' => 'unresolved',
        'count' => '17',
        'userCount' => 3,
        'numComments' => 1,
        'metadata' => ['type' => 'RuntimeException', 'value' => 'Boom'],
        'project' => ['id' => '7', 'name' => 'Backend', 'slug' => 'backend', 'platform' => 'php-laravel'],
        'firstSeen' => '2026-10-01T10:00:00Z',
        'lastSeen' => '2026-10-20T09:30:00Z',
        'assignedTo' => null,
        'firstRelease' => null,
        'lastRelease' => null,
        'permalink' => 'https://glitchtip.test/acme/issues/42',
    ], $overrides);
}
