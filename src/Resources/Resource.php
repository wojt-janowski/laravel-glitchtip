<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use Closure;
use Illuminate\Http\Client\Response;
use WojtJanowski\GlitchTip\Exceptions\MissingConfigurationException;
use WojtJanowski\GlitchTip\Support\Connector;
use WojtJanowski\GlitchTip\Support\CursorPage;

abstract readonly class Resource
{
    public function __construct(
        protected Connector $connector,
        private ?string $organization = null,
    ) {}

    protected function organization(): string
    {
        if ($this->organization === null || $this->organization === '') {
            throw MissingConfigurationException::organization();
        }

        return self::segment($this->organization);
    }

    /**
     * Encode one path segment. Release versions often contain slashes or "@".
     */
    protected static function segment(int|string $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $query
     * @param  Closure(array<string, mixed>): T  $map
     * @return CursorPage<T>
     */
    protected function page(string $path, array $query, Closure $map): CursorPage
    {
        $response = $this->connector->get($path, $query);

        return CursorPage::fromResponse(
            $response,
            $map,
            fn (string $cursor): CursorPage => $this->page($path, ['cursor' => $cursor] + $query, $map),
        );
    }

    /**
     * @template T
     *
     * @param  Closure(array<string, mixed>): T  $map
     * @return list<T>
     */
    protected static function listOf(Response $response, Closure $map): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = array_values(array_filter((array) $response->json(), is_array(...)));

        return array_map($map, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function object(Response $response): array
    {
        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
