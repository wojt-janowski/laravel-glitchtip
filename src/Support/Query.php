<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Support;

use BackedEnum;
use DateTimeInterface;
use GuzzleHttp\Psr7\Query as Psr7Query;

/**
 * Builds query strings the way django-ninja expects them: list values become
 * repeated keys (project=1&project=2), not PHP's project[0]=1 form.
 */
final class Query
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function build(array $parameters): string
    {
        $normalised = [];

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $values = array_values(array_filter(
                    array_map(self::scalar(...), $value),
                    fn (?string $item): bool => $item !== null,
                ));

                if ($values !== []) {
                    $normalised[$key] = $values;
                }

                continue;
            }

            $scalar = self::scalar($value);

            if ($scalar !== null) {
                $normalised[$key] = $scalar;
            }
        }

        return Psr7Query::build($normalised);
    }

    private static function scalar(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_scalar($value) => (string) $value,
            default => null,
        };
    }
}
