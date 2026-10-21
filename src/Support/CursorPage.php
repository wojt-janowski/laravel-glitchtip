<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Support;

use ArrayIterator;
use Closure;
use Countable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use Traversable;

/**
 * One page of a cursor-paginated GlitchTip listing.
 *
 * GlitchTip paginates like Sentry: a Link header carries rel="next" and
 * rel="previous" entries, each with results="true|false" and a cursor.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final readonly class CursorPage implements Countable, IteratorAggregate
{
    /**
     * @param  list<TItem>  $items
     * @param  (Closure(string): self<TItem>)|null  $fetch
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor = null,
        public ?string $previousCursor = null,
        public ?int $hits = null,
        private ?Closure $fetch = null,
    ) {}

    /**
     * @template T
     *
     * @param  Closure(array<string, mixed>): T  $map
     * @param  Closure(string): self<T>  $fetch
     * @return self<T>
     */
    public static function fromResponse(Response $response, Closure $map, Closure $fetch): self
    {
        $links = self::parseLinkHeader($response->header('Link'));
        $hits = $response->header('X-Hits');

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values(array_filter((array) $response->json(), is_array(...)));

        return new self(
            items: array_map($map, $rows),
            nextCursor: $links['next'] ?? null,
            previousCursor: $links['previous'] ?? null,
            hits: is_numeric($hits) ? (int) $hits : null,
            fetch: $fetch,
        );
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    /**
     * @return self<TItem>|null
     */
    public function next(): ?self
    {
        if ($this->nextCursor === null || ! $this->fetch instanceof Closure) {
            return null;
        }

        return ($this->fetch)($this->nextCursor);
    }

    /**
     * @return self<TItem>|null
     */
    public function previous(): ?self
    {
        if ($this->previousCursor === null || ! $this->fetch instanceof Closure) {
            return null;
        }

        return ($this->fetch)($this->previousCursor);
    }

    /**
     * Items on this page only.
     *
     * @return Collection<int, TItem>
     */
    public function collect(): Collection
    {
        return new Collection($this->items);
    }

    /**
     * Every item from this page onwards, fetching later pages on demand.
     *
     * @return LazyCollection<int, TItem>
     */
    public function lazy(): LazyCollection
    {
        return LazyCollection::make(function () {
            $page = $this;

            while ($page instanceof self) {
                yield from $page->items;

                $page = $page->next();
            }
        })->values();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, TItem>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return array<string, string> cursor keyed by rel, only where results="true"
     */
    public static function parseLinkHeader(string $header): array
    {
        $cursors = [];

        foreach (explode(',', $header) as $link) {
            if (! preg_match('/rel="(?<rel>[^"]+)"/', $link, $rel)) {
                continue;
            }

            if (! preg_match('/results="true"/', $link)) {
                continue;
            }

            if (preg_match('/cursor="(?<cursor>[^"]+)"/', $link, $cursor)) {
                $cursors[$rel['rel']] = $cursor['cursor'];
            }
        }

        return $cursors;
    }
}
