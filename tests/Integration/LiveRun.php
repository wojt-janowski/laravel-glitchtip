<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Tests\Integration;

use Closure;
use Illuminate\Http\Client\Factory;
use RuntimeException;
use Throwable;
use WojtJanowski\GlitchTip\Data\Project;
use WojtJanowski\GlitchTip\Data\ProjectInput;
use WojtJanowski\GlitchTip\Data\Team;
use WojtJanowski\GlitchTip\GlitchTip;
use WojtJanowski\GlitchTip\Support\Connector;

/**
 * One integration test's view of a real GlitchTip organization. Everything it
 * creates is named with a unique "it-" prefix and removed by runCleanups(), in
 * reverse order, whether or not the test passed.
 */
final class LiveRun
{
    /** @var list<array{label: string, callback: Closure(): mixed}> */
    private array $cleanups = [];

    public readonly string $prefix;

    public readonly GlitchTip $client;

    public function __construct(
        public readonly string $url,
        string $token,
        public readonly string $organization,
    ) {
        $this->prefix = 'it-'.date('YmdHis').'-'.bin2hex(random_bytes(2));
        $this->client = app(GlitchTip::class)->using($url, $token, $organization);
    }

    public static function fromEnvironment(): ?self
    {
        $url = getenv('GLITCHTIP_INTEGRATION_URL');
        $token = getenv('GLITCHTIP_INTEGRATION_TOKEN');
        $organization = getenv('GLITCHTIP_INTEGRATION_ORGANIZATION');

        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '' || ! is_string($organization) || $organization === '') {
            return null;
        }

        return new self($url, $token, $organization);
    }

    /**
     * A client with no token at all, for the endpoints that need none.
     */
    public function anonymous(): GlitchTip
    {
        return new GlitchTip(new Connector(app(Factory::class), $this->url, null), $this->organization);
    }

    public function name(string $suffix): string
    {
        return $this->prefix.'-'.$suffix;
    }

    /**
     * Register how to undo something the test just created.
     */
    public function defer(string $label, Closure $callback): void
    {
        $this->cleanups[] = ['label' => $label, 'callback' => $callback];
    }

    /**
     * A fresh team and a project owned by it, both removed afterwards.
     *
     * @return array{0: Team, 1: Project}
     */
    public function teamAndProject(): array
    {
        $team = $this->client->teams()->create($this->name('team'));
        $this->defer("team {$team->slug}", fn () => $this->client->teams()->delete($team->slug));

        $project = $this->client->projects()->create($team->slug, new ProjectInput(name: $this->name('project'), platform: 'php-laravel'));
        $this->defer("project {$project->slug}", fn () => $this->client->projects()->delete((string) $project->slug));

        return [$team, $project];
    }

    /**
     * Retry until the callback returns something other than null.
     *
     * @template T
     *
     * @param  Closure(): (T|null)  $callback
     * @return T
     */
    public function eventually(string $what, Closure $callback, int $seconds = 60): mixed
    {
        $deadline = time() + $seconds;

        do {
            $result = $callback();

            if ($result !== null) {
                return $result;
            }

            usleep(1_000_000);
        } while (time() < $deadline);

        throw new RuntimeException("Timed out after {$seconds}s waiting for {$what}.");
    }

    /**
     * Run every registered cleanup, newest first.
     *
     * @return list<string> descriptions of cleanups that failed
     */
    public function runCleanups(): array
    {
        $failures = [];

        foreach (array_reverse($this->cleanups) as $cleanup) {
            try {
                ($cleanup['callback'])();
            } catch (Throwable $exception) {
                $failures[] = "{$cleanup['label']}: {$exception->getMessage()}";
            }
        }

        $this->cleanups = [];

        return $failures;
    }
}
