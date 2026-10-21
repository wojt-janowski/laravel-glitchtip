<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use DateTimeInterface;
use WojtJanowski\GlitchTip\Data\NPlusOnePattern;
use WojtJanowski\GlitchTip\Data\SpanGroup;
use WojtJanowski\GlitchTip\Data\TransactionGroup;
use WojtJanowski\GlitchTip\Data\TransactionTrendPoint;
use WojtJanowski\GlitchTip\Enums\SpanGroupSort;
use WojtJanowski\GlitchTip\Enums\TransactionGroupSort;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Performance extends Resource
{
    /**
     * Transactions grouped by name, e.g. "GET /orders/{id}".
     *
     * @param  list<int>  $projects  project ids
     * @return CursorPage<TransactionGroup>
     */
    public function transactionGroups(
        ?string $query = null,
        array $projects = [],
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?TransactionGroupSort $sort = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): CursorPage {
        return $this->page($this->path('transaction-groups/'), [
            'query' => $query,
            'project' => $projects,
            'start' => $start,
            'end' => $end,
            'sort' => $sort,
            'limit' => $limit,
            'cursor' => $cursor,
        ], TransactionGroup::fromArray(...));
    }

    public function transactionGroup(int $group): TransactionGroup
    {
        return TransactionGroup::fromArray(self::object($this->connector->get($this->path("transaction-groups/{$group}/"))));
    }

    /**
     * The slowest span groups within one transaction group.
     *
     * @return list<SpanGroup>
     */
    public function transactionSpans(int $group, ?DateTimeInterface $start = null, ?DateTimeInterface $end = null): array
    {
        return self::listOf($this->connector->get($this->path("transaction-groups/{$group}/spans/"), [
            'start' => $start,
            'end' => $end,
        ]), SpanGroup::fromArray(...));
    }

    /**
     * Duration and volume over time for one transaction group.
     *
     * @return list<TransactionTrendPoint>
     */
    public function trend(int $group, ?DateTimeInterface $start = null, ?DateTimeInterface $end = null): array
    {
        return self::listOf($this->connector->get($this->path("transaction-groups/{$group}/trend/"), [
            'start' => $start,
            'end' => $end,
        ]), TransactionTrendPoint::fromArray(...));
    }

    /**
     * Span groups across the organization, e.g. the most expensive queries.
     *
     * @param  list<int>  $projects
     * @param  string|null  $op  span op, e.g. "db.sql.query"
     * @return list<SpanGroup>
     */
    public function spanGroups(
        array $projects = [],
        ?string $op = null,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?SpanGroupSort $sort = null,
        ?int $limit = null,
    ): array {
        return self::listOf($this->connector->get($this->path('span-groups/'), [
            'project' => $projects,
            'op' => $op,
            'start' => $start,
            'end' => $end,
            'sort' => $sort,
            'limit' => $limit,
        ]), SpanGroup::fromArray(...));
    }

    /**
     * Spans repeated within single transactions, typically N+1 queries.
     *
     * @param  list<int>  $projects
     * @param  float|null  $threshold  minimum spans per transaction to report
     * @return list<NPlusOnePattern>
     */
    public function nPlusOne(
        array $projects = [],
        ?string $op = null,
        ?float $threshold = null,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
        ?int $limit = null,
    ): array {
        return self::listOf($this->connector->get($this->path('n-plus-one/'), [
            'project' => $projects,
            'op' => $op,
            'threshold' => $threshold,
            'start' => $start,
            'end' => $end,
            'limit' => $limit,
        ]), NPlusOnePattern::fromArray(...));
    }

    private function path(string $suffix): string
    {
        return "organizations/{$this->organization()}/{$suffix}";
    }
}
