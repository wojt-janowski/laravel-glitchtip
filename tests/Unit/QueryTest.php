<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Enums\IssueSort;
use WojtJanowski\GlitchTip\Support\Query;

describe('Query::build', function (): void {
    it('repeats keys for list values the way django-ninja expects', function (): void {
        expect(Query::build(['project' => [1, 2], 'environment' => ['production']]))
            ->toBe('project=1&project=2&environment=production');
    });

    it('drops nulls and empty lists', function (): void {
        expect(Query::build(['query' => null, 'project' => [], 'limit' => 25]))->toBe('limit=25');
    });

    it('serialises booleans, enums and dates', function (): void {
        $query = Query::build([
            'owner' => false,
            'sort' => IssueSort::LastSeenDesc,
            'start' => CarbonImmutable::parse('2026-10-01T00:00:00+00:00'),
        ]);

        expect($query)->toBe('owner=false&sort=-last_seen&start=2026-10-01T00%3A00%3A00%2B00%3A00');
    });
});
