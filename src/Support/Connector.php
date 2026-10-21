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
        private ?BrowserSession $session = null,
    ) {}

    /**
     * A connector for another server and token that keeps this one's HTTP
     * client, timeouts and retry settings.
     */
    public function withCredentials(string $url, string $token): self
    {
        return new self(
            http: $this->http,
            url: $url,
            token: $token,
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            retryTimes: $this->retryTimes,
            retrySleepMilliseconds: $this->retrySleepMilliseconds,
        );
    }

    /**
     * A connector that acts as a user signed in to GlitchTip in the browser,
     * keeping this one's HTTP client, timeouts and retry settings.
     */
    public function withSession(string $url, BrowserSession $session): self
    {
        return new self(
            http: $this->http,
            url: $url,
            token: null,
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            retryTimes: $this->retryTimes,
            retrySleepMilliseconds: $this->retrySleepMilliseconds,
            session: $session,
        );
    }

    public function url(): string
    {
        return rtrim($this->url, '/');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * A GET to an endpoint that needs no token, such as the instance settings.
     * The token is still sent when one is configured.
     *
     * @param  array<string, mixed>  $query
     */
    public function getPublic(string $path, array $query = []): Response
    {
        return $this->send('GET', $path, $query, authenticate: false);
    }

    /**
     * @param  array<array-key, mixed>  $body  an object, or a list for endpoints that take a JSON array
     * @param  array<string, mixed>  $query
     */
    public function post(string $path, array $body = [], array $query = []): Response
    {
        return $this->send('POST', $path, $query, $body);
    }

    /**
     * A POST to an endpoint that needs no token, such as a heartbeat.
     *
     * @param  array<array-key, mixed>  $body
     */
    public function postPublic(string $path, array $body = []): Response
    {
        return $this->send('POST', $path, [], $body, authenticate: false);
    }

    /**
     * POST files as multipart/form-data, all under one field name.
     *
     * @param  list<array{contents: string, filename: string}>  $files
     */
    public function postFiles(string $path, string $field, array $files): Response
    {
        // Not asJson(): its Content-Type header would override the multipart boundary.
        $request = $this->request('POST', json: false);

        foreach ($files as $file) {
            $request->attach($field, $file['contents'], $file['filename']);
        }

        return $this->verify('POST', $path, $request->post($this->uri($path)));
    }

    /**
     * @param  array<array-key, mixed>  $body
     * @param  array<string, mixed>  $query
     */
    public function put(string $path, array $body = [], array $query = []): Response
    {
        return $this->send('PUT', $path, $query, $body);
    }

    /**
     * A DELETE that works without a token, such as clearing a wizard hash.
     */
    public function deletePublic(string $path): Response
    {
        return $this->send('DELETE', $path, authenticate: false);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<array-key, mixed>|null  $body  for the few DELETEs that take one
     */
    public function delete(string $path, array $query = [], ?array $body = null): Response
    {
        return $this->send('DELETE', $path, $query, $body);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<array-key, mixed>|null  $body
     */
    private function send(string $method, string $path, array $query = [], ?array $body = null, bool $authenticate = true): Response
    {
        $options = match (true) {
            $body === null => [],
            // An empty body must still be a JSON object, not [].
            $body !== [] && array_is_list($body) => ['json' => $body],
            default => ['json' => (object) $body],
        };

        $response = $this->request($method, $authenticate)->send($method, $this->uri($path, $query), $options);

        return $this->verify($method, $path, $response);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function uri(string $path, array $query = []): string
    {
        $queryString = Query::build($query);
        // Paths are relative to /api/0/ unless they name another API root, like /api/settings/.
        $base = str_starts_with($path, '/api/') ? $path : '/api/0/'.ltrim($path, '/');

        return $base.($queryString === '' ? '' : '?'.$queryString);
    }

    private function verify(string $method, string $path, Response $response): Response
    {
        if ($response->failed()) {
            throw RequestFailedException::fromResponse($method, '/'.ltrim($path, '/'), $response);
        }

        return $response;
    }

    private function request(string $method, bool $authenticate = true, bool $json = true): PendingRequest
    {
        $hasToken = $this->token !== null && $this->token !== '';

        if ($authenticate && ! $hasToken && $this->session === null) {
            throw MissingConfigurationException::token();
        }

        $request = $this->http
            ->baseUrl(rtrim($this->url, '/'))
            ->acceptJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

        if ($json) {
            $request->asJson();
        }

        if ($this->session !== null) {
            $request->withHeaders($this->session->headers($this->url));
        } elseif ($hasToken) {
            $request->withToken((string) $this->token);
        }

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
