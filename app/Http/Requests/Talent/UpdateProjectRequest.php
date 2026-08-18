<?php

namespace App\Http\Requests\Talent;

/**
 * Update a portfolio project. Same shape as the create request — a project is
 * edited whole, so `skill_ids` stays required (it can never drop to zero,
 * ADR-X).
 */
class UpdateProjectRequest extends StoreProjectRequest
{
}
