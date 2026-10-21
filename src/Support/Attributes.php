<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Typed reads from a decoded GlitchTip payload, tolerant of missing keys.
 *
 * @internal
 */
final readonly class Attributes
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private array $data) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function of(array $data): self
    {
        return new self($data);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function string(string $key, string $default = ''): string
    {
        return $this->nullableString($key) ?? $default;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    public function int(string $key, int $default = 0): int
    {
        return $this->nullableInt($key) ?? $default;
    }

    public function nullableInt(string $key): ?int
    {
        $value = $this->data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function float(string $key, float $default = 0.0): float
    {
        return $this->nullableFloat($key) ?? $default;
    }

    public function nullableFloat(string $key): ?float
    {
        $value = $this->data[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return $this->nullableBool($key) ?? $default;
    }

    public function nullableBool(string $key): ?bool
    {
        $value = $this->data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    public function date(string $key): CarbonImmutable
    {
        return $this->nullableDate($key) ?? CarbonImmutable::createFromTimestamp(0);
    }

    public function nullableDate(string $key): ?CarbonImmutable
    {
        $value = $this->nullableString($key);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nullableObject(string $key): ?array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function objects(string $key): array
    {
        /** @var list<array<string, mixed>> */
        return array_values(array_filter($this->array($key), is_array(...)));
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        return array_values(array_map(strval(...), array_filter($this->array($key), is_scalar(...))));
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    public function enum(string $key, string $enum): ?\BackedEnum
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) || is_int($value) ? $enum::tryFrom($value) : null;
    }
}
