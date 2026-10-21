<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Support;

use InvalidArgumentException;

/**
 * The Django session a GlitchTip user signed in with in the browser.
 *
 * GlitchTip's own UI authenticates with these two cookies, not a token, so an
 * app served from the same origin can act as that user, with their role and
 * under their name in GlitchTip's records, by passing them along.
 */
final readonly class BrowserSession
{
    public function __construct(
        public string $sessionId,
        public string $csrfToken,
    ) {
        if (trim($sessionId) === '' || trim($csrfToken) === '') {
            throw new InvalidArgumentException('A GlitchTip browser session needs a session ID and a CSRF token.');
        }
    }

    /**
     * Headers that authenticate one request as the signed-in user. Django
     * checks the Origin of unsafe requests against its own host, so it is
     * set to the instance even though the request comes from a server.
     *
     * @return array<string, string>
     */
    public function headers(string $url): array
    {
        $parts = parse_url($url);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');

        return [
            'Cookie' => "sessionid={$this->sessionId}; csrftoken={$this->csrfToken}",
            'X-CSRFToken' => $this->csrfToken,
            'Origin' => $origin,
            'Referer' => $origin.'/',
        ];
    }
}
