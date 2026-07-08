<?php

namespace App\Services;

use App\Models\TalentType;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Admin profession/template catalog management (Phase 3A). Edit a talent type's
 * `default_blocks` (changes the block layout NEW talents of that type are seeded
 * with — existing profiles are untouched) and add professions without code.
 * Gated on `manage-flows` (TalentTypePolicy@manage); transactional +
 * activity-logged.
 */
class ProfessionCatalogService extends AdminService
{
    /**
     * @param  list<string>  $blocks  ordered default block keys
     */
    public function updateDefaultBlocks(User $admin, TalentType $type, array $blocks): TalentType
    {
        $this->authorizeAdmin($admin, 'manage', $type);

        return $this->runInTransaction(function () use ($admin, $type, $blocks): TalentType {
            $previous = $type->default_blocks;
            $type->update(['default_blocks' => array_values($blocks)]);
            $this->record($admin, $type, 'catalog', 'talent_type.default_blocks_updated', [
                'from' => $previous, 'to' => array_values($blocks),
            ]);

            return $type->refresh();
        }, ['talent_type_id' => $type->getKey()]);
    }

    /**
     * Add a profession to the catalog (no code change needed).
     *
     * @param  array<string, mixed>  $data
     */
    public function addProfession(User $admin, array $data): TalentType
    {
        $this->authorizeAdmin($admin, 'manage', TalentType::class);

        return $this->runInTransaction(function () use ($admin, $data): TalentType {
            $type = TalentType::create([
                'name' => $data['name'], // translatable {en, ar}
                'slug' => $data['slug'] ?? Str::slug(is_array($data['name']) ? ($data['name']['en'] ?? reset($data['name'])) : $data['name']),
                'category' => $data['category'],
                'default_blocks' => array_values($data['default_blocks'] ?? []),
                'icon' => $data['icon'] ?? null,
                'description' => $data['description'] ?? null,
            ]);
            $this->record($admin, $type, 'catalog', 'talent_type.created', ['slug' => $type->slug]);

            return $type;
        }, ['slug' => $data['slug'] ?? null]);
    }
}
