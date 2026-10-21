<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\User;
use WojtJanowski\GlitchTip\Data\UserEmail;
use WojtJanowski\GlitchTip\Data\UserOptions;
use WojtJanowski\GlitchTip\Support\CursorPage;

/**
 * The token's user account. GlitchTip only lets a user manage themselves, so
 * every method defaults to "me".
 */
final readonly class Users extends Resource
{
    /**
     * Lists only the current user; kept for Sentry compatibility.
     *
     * @return CursorPage<User>
     */
    public function list(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page('users/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], User::fromArray(...));
    }

    public function get(int|string $user = 'me'): User
    {
        return User::fromArray(self::object($this->connector->get($this->path($user))));
    }

    /**
     * Replace the user's options, and rename them when a name is given.
     */
    public function update(UserOptions $options, ?string $name = null, int|string $user = 'me'): User
    {
        $body = ['options' => $options->toArray()];

        if ($name !== null) {
            $body['name'] = $name;
        }

        return User::fromArray(self::object($this->connector->put($this->path($user), $body)));
    }

    /**
     * Delete the user account.
     */
    public function delete(int|string $user = 'me'): void
    {
        $this->connector->delete($this->path($user));
    }

    /**
     * @return list<UserEmail>
     */
    public function emails(int|string $user = 'me'): array
    {
        return self::listOf($this->connector->get($this->path($user).'emails/'), UserEmail::fromArray(...));
    }

    /**
     * Add an unverified email address and send a confirmation.
     */
    public function addEmail(string $email, int|string $user = 'me'): UserEmail
    {
        return UserEmail::fromArray(self::object($this->connector->post($this->path($user).'emails/', ['email' => $email])));
    }

    /**
     * Make a verified address the primary one.
     */
    public function makeEmailPrimary(string $email, int|string $user = 'me'): UserEmail
    {
        return UserEmail::fromArray(self::object($this->connector->put($this->path($user).'emails/', ['email' => $email])));
    }

    public function deleteEmail(string $email, int|string $user = 'me'): void
    {
        $this->connector->delete($this->path($user).'emails/', body: ['email' => $email]);
    }

    public function sendEmailConfirmation(string $email, int|string $user = 'me'): void
    {
        $this->connector->post($this->path($user).'emails/confirm/', ['email' => $email]);
    }

    /**
     * Whether the user is subscribed to project notifications by default.
     */
    public function subscribedByDefault(int|string $user = 'me'): bool
    {
        return (bool) $this->connector->get($this->path($user).'notifications/')->json('subscribeByDefault', true);
    }

    public function updateNotifications(bool $subscribeByDefault, int|string $user = 'me'): bool
    {
        return (bool) $this->connector->put($this->path($user).'notifications/', ['subscribeByDefault' => $subscribeByDefault])
            ->json('subscribeByDefault', $subscribeByDefault);
    }

    /**
     * Generate a set of recovery codes for two-factor authentication. They
     * take effect once confirmed with saveRecoveryCodes().
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        $codes = $this->connector->get('generate-recovery-codes/')->json('codes', []);

        return array_values(array_map(strval(...), array_filter((array) $codes, is_scalar(...))));
    }

    /**
     * Save the generated recovery codes, confirming with one of them.
     */
    public function saveRecoveryCodes(string $code): void
    {
        $this->connector->post('generate-recovery-codes/', ['code' => $code]);
    }

    private function path(int|string $user): string
    {
        return 'users/'.self::segment($user).'/';
    }
}
