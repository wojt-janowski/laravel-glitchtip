<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Support;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Throwable;
use WojtJanowski\GlitchTip\Exceptions\MissingConfigurationException;
use WojtJanowski\GlitchTip\Exceptions\RequestFailedException;

/**
 * Thin wrapper around Laravel's HTTP client that authenticates, retries
 * idempotent requests and turns failures into RequestFailedException.
 */
final readonly class Connector
{
    private const array RETRYABLE_STATUSES = [429, 502, 503, 504];

    public function __construct(
        private Factory $http,
        private string $url,
        private ?string $token,
        private int $timeout = 30,
        private int $connectTimeout = 10,
        private int $retryTimes = 2,
        private int $retrySleepMilliseconds = 500,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     */
    public function post(string $path, array $body = [], array $query = []): Response
    {
        return $this->send('POST', $path, $query, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $query
     */
    public function put(string $path, array $body = [], array $query = []): Response
    {
        return $this->send('PUT', $path, $query, $body);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function delete(string $path, array $query = []): Response
    {
        return $this->send('DELETE', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     */
    private function send(string $method, string $path, array $query = [], ?array $body = null): Response
    {
        $queryString = Query::build($query);
        // Paths are relative to /api/0/ unless they name another API root, like /api/settings/.
        $base = str_starts_with($path, '/api/') ? $path : '/api/0/'.ltrim($path, '/');
        $uri = $base.($queryString === '' ? '' : '?'.$queryString);

        $options = $body === null ? [] : ['json' => (object) $body];

        $response = $this->request($method)->send($method, $uri, $options);

        if ($response->failed()) {
            throw RequestFailedException::fromResponse($method, '/'.ltrim($path, '/'), $response);
        }

        return $response;
    }

    private function request(string $method): PendingRequest
    {
        if ($this->token === null || $this->token === '') {
            throw MissingConfigurationException::token();
        }

        $request = $this->http
            ->baseUrl(rtrim($this->url, '/'))
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

        // POSTs create things, so retrying one could duplicate it.
        if ($method !== 'POST' && $this->retryTimes > 0) {
            $request->retry($this->retryTimes, $this->retrySleepMilliseconds, $this->shouldRetry(), throw: false);
        }

        return $request;
    }

    /**
     * @return Closure(Throwable): bool
     */
    private function shouldRetry(): Closure
    {
        return fn (Throwable $exception): bool => $exception instanceof ConnectionException
            || ($exception instanceof RequestException && in_array($exception->response->status(), self::RETRYABLE_STATUSES, true));
    }
}
