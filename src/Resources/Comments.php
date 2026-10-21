<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Comment;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Comments extends Resource
{
    /**
     * @return CursorPage<Comment>
     */
    public function list(int|string $issue, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path($issue), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Comment::fromArray(...));
    }

    public function create(int|string $issue, string $text): Comment
    {
        return Comment::fromArray(self::object($this->connector->post($this->path($issue), ['data' => ['text' => $text]])));
    }

    public function update(int|string $issue, int $comment, string $text): Comment
    {
        return Comment::fromArray(self::object(
            $this->connector->put($this->path($issue).$comment.'/', ['data' => ['text' => $text]]),
        ));
    }

    public function delete(int|string $issue, int $comment): void
    {
        $this->connector->delete($this->path($issue).$comment.'/');
    }

    private function path(int|string $issue): string
    {
        return 'issues/'.self::segment($issue).'/comments/';
    }
}
