<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use WojtJanowski\GlitchTip\Enums\IssueStatus;

/**
 * A partial issue update. GlitchTip only applies the fields that are sent, so
 * this tracks which fields were set; combine updates with with().
 *
 *     IssueUpdate::resolved()->with(IssueUpdate::assignedTo('team:backend'))
 */
final readonly class IssueUpdate
{
    /**
     * @param  array<string, mixed>  $fields
     */
    private function __construct(private array $fields) {}

    public static function status(IssueStatus $status): self
    {
        return new self(['status' => $status->value]);
    }

    public static function resolved(): self
    {
        return self::status(IssueStatus::Resolved);
    }

    public static function unresolved(): self
    {
        return self::status(IssueStatus::Unresolved);
    }

    public static function ignored(): self
    {
        return self::status(IssueStatus::Ignored);
    }

    /**
     * Resolve now, and reopen if the issue recurs after the project's next release.
     */
    public static function resolvedInNextRelease(): self
    {
        return new self(['status' => 'resolvedInNextRelease']);
    }

    /**
     * Resolve in a specific release version.
     */
    public static function resolvedInRelease(string $version): self
    {
        return new self([
            'status' => IssueStatus::Resolved->value,
            'statusDetails' => ['inRelease' => $version],
        ]);
    }

    /**
     * Assign to "user:<user id>", "team:<team slug>" or a member's email address.
     * Pass null to unassign.
     */
    public static function assignedTo(?string $assignee): self
    {
        return new self(['assignedTo' => $assignee]);
    }

    public static function unassigned(): self
    {
        return self::assignedTo(null);
    }

    /**
     * Merge every matched issue into the newest one. Bulk updates only; all
     * matched issues must belong to the same project.
     */
    public static function merged(): self
    {
        return new self(['merge' => 1]);
    }

    /**
     * A new update with the other update's fields layered on top.
     */
    public function with(self $other): self
    {
        return new self(array_replace($this->fields, $other->fields));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
