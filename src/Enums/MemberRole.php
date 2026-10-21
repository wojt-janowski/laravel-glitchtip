<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Enums;

/**
 * Roles a member can hold in an organization, lowest first.
 */
enum MemberRole: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Manager = 'manager';
    case Owner = 'owner';
}
