<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Environment;
use WojtJanowski\GlitchTip\Enums\EnvironmentVisibility;
use WojtJanowski\GlitchTip\Support\CursorPage;

final readonly class Environments extends Resource
{
    /**
     * @return CursorPage<Environment>
     */
    public function list(?EnvironmentVisibility $visibility = null, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("organizations/{$this->organization()}/environments/", [
            'visibility' => $visibility,
            'limit' => $limit,
            'cursor' => $cursor,
        ], Environment::fromArray(...));
    }
}
