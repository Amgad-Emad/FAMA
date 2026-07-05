<?php

namespace App\States\TalentProfile;

/** Retired; terminal (soft-delete/purge happen separately). */
class Archived extends TalentProfileState
{
    public static string $name = 'archived';
}
