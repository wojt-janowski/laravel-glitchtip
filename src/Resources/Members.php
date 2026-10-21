<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Resources;

use WojtJanowski\GlitchTip\Data\Invitation;
use WojtJanowski\GlitchTip\Data\Member;
use WojtJanowski\GlitchTip\Enums\MemberRole;
use WojtJanowski\GlitchTip\Support\CursorPage;

/**
 * Organization members and invitations.
 */
final readonly class Members extends Resource
{
    /**
     * @return CursorPage<Member>
     */
    public function list(?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page($this->path(), [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Member::fromArray(...));
    }

    /**
     * @return CursorPage<Member>
     */
    public function forTeam(string $team, ?int $limit = null, ?string $cursor = null): CursorPage
    {
        return $this->page("teams/{$this->organization()}/".self::segment($team).'/members/', [
            'limit' => $limit,
            'cursor' => $cursor,
        ], Member::fromArray(...));
    }

    /**
     * @param  int|string  $member  member id, or "me"
     */
    public function get(int|string $member): Member
    {
        return Member::fromArray(self::object($this->connector->get($this->path($member))));
    }

    /**
     * Invite someone by email. The result's inviteLink can be shared when
     * $sendEmail is false.
     *
     * @param  array<int|string, string>  $teams  team slugs, or slug => team role
     */
    public function invite(string $email, MemberRole $role = MemberRole::Member, array $teams = [], bool $sendEmail = true, bool $reinvite = true): Member
    {
        return Member::fromArray(self::object($this->connector->post($this->path(), [
            'email' => $email,
            'orgRole' => $role->value,
            'teamRoles' => $this->teamRoles($teams),
            'sendInvite' => $sendEmail,
            'reinvite' => $reinvite,
        ])));
    }

    /**
     * Change a member's organization role, and optionally their teams.
     *
     * @param  array<int|string, string>|null  $teams  team slugs, or slug => team role; null leaves teams unchanged
     */
    public function update(int|string $member, MemberRole $role, ?array $teams = null): Member
    {
        $body = ['orgRole' => $role->value];

        if ($teams !== null) {
            $body['teamRoles'] = $this->teamRoles($teams);
        }

        return Member::fromArray(self::object($this->connector->put($this->path($member), $body)));
    }

    /**
     * Remove a member, or revoke a pending invitation.
     */
    public function delete(int|string $member): void
    {
        $this->connector->delete($this->path($member));
    }

    /**
     * Make this member the organization's only owner.
     */
    public function makeOwner(int|string $member): Member
    {
        return Member::fromArray(self::object($this->connector->post($this->path($member).'set_owner/')));
    }

    /**
     * Look up an invitation from its link. Needs no token.
     */
    public function invitation(int $member, string $token): Invitation
    {
        return Invitation::fromArray(self::object($this->connector->getPublic($this->invitationPath($member, $token))));
    }

    /**
     * Accept an invitation as the token's user.
     */
    public function acceptInvitation(int $member, string $token): Invitation
    {
        return Invitation::fromArray(self::object($this->connector->post($this->invitationPath($member, $token), ['acceptInvite' => true])));
    }

    /**
     * @param  array<int|string, string>  $teams
     * @return list<array{teamSlug: string, role: string}>
     */
    private function teamRoles(array $teams): array
    {
        $roles = [];

        foreach ($teams as $key => $value) {
            $roles[] = is_int($key)
                ? ['teamSlug' => $value, 'role' => '']
                : ['teamSlug' => $key, 'role' => $value];
        }

        return $roles;
    }

    private function invitationPath(int $member, string $token): string
    {
        return "accept/{$member}/".self::segment($token).'/';
    }

    private function path(int|string|null $member = null): string
    {
        $path = "organizations/{$this->organization()}/members/";

        return $member === null ? $path : $path.self::segment($member).'/';
    }
}
