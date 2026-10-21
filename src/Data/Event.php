<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Data;

use Carbon\CarbonImmutable;
use WojtJanowski\GlitchTip\Support\Attributes;

final readonly class Event
{
    /**
     * @param  array<string, string>  $tags
     * @param  list<array<string, mixed>>  $entries  exception, breadcrumbs, request, ... entries as GlitchTip sends them
     * @param  array<string, mixed>  $contexts
     * @param  array<string, mixed>|null  $user
     * @param  array<string, mixed>|null  $sdk
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $id,
        public string $eventId,
        public string $issueId,
        public int $projectId,
        public string $title,
        public string $message,
        public ?string $culprit,
        public string $type,
        public ?string $platform,
        public CarbonImmutable $dateCreated,
        public CarbonImmutable $dateReceived,
        public array $tags = [],
        public array $entries = [],
        public array $contexts = [],
        public ?array $user = null,
        public ?array $sdk = null,
        public array $metadata = [],
        public ?string $nextEventId = null,
        public ?string $previousEventId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $a = Attributes::of($data);

        $tags = [];

        foreach ($a->objects('tags') as $tag) {
            $tagAttributes = Attributes::of($tag);
            $tags[$tagAttributes->string('key')] = $tagAttributes->string('value');
        }

        /** @var array<string, mixed> $contexts */
        $contexts = $a->array('contexts');

        /** @var array<string, mixed> $metadata */
        $metadata = $a->array('metadata');

        return new self(
            id: $a->string('id'),
            eventId: $a->string('eventID'),
            issueId: $a->string('groupID'),
            projectId: $a->int('projectID'),
            title: $a->string('title'),
            message: $a->string('message'),
            culprit: $a->nullableString('culprit'),
            type: $a->string('type'),
            platform: $a->nullableString('platform'),
            dateCreated: $a->date('dateCreated'),
            dateReceived: $a->date('dateReceived'),
            tags: $tags,
            entries: $a->objects('entries'),
            contexts: $contexts,
            user: $a->nullableObject('user'),
            sdk: $a->nullableObject('sdk'),
            metadata: $metadata,
            nextEventId: $a->nullableString('nextEventID'),
            previousEventId: $a->nullableString('previousEventID'),
        );
    }

    /**
     * The data of the first entry of the given type, e.g. "exception" or "breadcrumbs".
     *
     * @return array<string, mixed>|null
     */
    public function entry(string $type): ?array
    {
        foreach ($this->entries as $entry) {
            if (($entry['type'] ?? null) === $type) {
                $data = $entry['data'] ?? null;

                return is_array($data) ? $data : null;
            }
        }

        return null;
    }
}
