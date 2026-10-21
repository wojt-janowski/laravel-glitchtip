<?php

declare(strict_types=1);

use WojtJanowski\GlitchTip\Data\IssueFilter;
use WojtJanowski\GlitchTip\Data\IssueUpdate;

describe('IssueUpdate', function (): void {
    it('sends only the fields that were set', function (): void {
        expect(IssueUpdate::resolved()->toArray())->toBe(['status' => 'resolved']);
    });

    it('sends an explicit null to unassign', function (): void {
        expect(IssueUpdate::unassigned()->toArray())->toBe(['assignedTo' => null]);
    });

    it('layers combined updates', function (): void {
        $update = IssueUpdate::resolvedInRelease('1.4.0')->with(IssueUpdate::assignedTo('team:backend'));

        expect($update->toArray())->toBe([
            'status' => 'resolved',
            'statusDetails' => ['inRelease' => '1.4.0'],
            'assignedTo' => 'team:backend',
        ]);
    });
});

describe('IssueFilter', function (): void {
    it('refuses an empty filter, which GlitchTip would apply to every issue', function (): void {
        new IssueFilter;
    })->throws(InvalidArgumentException::class);

    it('allows targeting every issue deliberately', function (): void {
        expect(IssueFilter::everything()->toQuery())->toMatchArray(['id' => [], 'query' => null]);
    });
});
