<?php

namespace App\Services;

use App\Models\BlockType;
use App\Models\TalentType;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Admin skills template management (the preselection side of the block
 * governance split). Edits a talent type's `default_blocks` — the ordered
 * PRESELECTION new talents of that skill are seeded with — choosing only among
 * blocks the Block Catalog Manager makes eligible; and adds skills without
 * code. Existing profiles are untouched. Gated on `manage-flows`
 * (TalentTypePolicy@manage); transactional + activity-logged.
 */
class SkillCatalogService extends AdminService
{
    public function __construct(private readonly ProfileBlockService $profileBlocks) {}

    /**
     * Persist the ordered preselection. Eligibility is the catalog's decision,
     * not this page's: a key may only be ADDED if it is active + eligible for
     * the skill (universal, or gated to its category/type). Keys already
     * preselected are allowed to stay or be reordered/removed even if they have
     * since become ineligible — the UI flags them; forcing a cleanup here would
     * block unrelated edits.
     *
     * @param  list<string>  $blocks  ordered default block keys
     */
    public function updateDefaultBlocks(User $admin, TalentType $type, array $blocks): TalentType
    {
        $this->authorizeAdmin($admin, 'manage', $type);

        $current = collect($type->default_blocks ?? []);
        $added = collect($blocks)->reject(fn (string $k) => $current->contains($k));
        if ($added->isNotEmpty()) {
            $eligible = BlockType::query()->with(['categories', 'talentTypes:talent_types.id'])
                ->whereIn('key', $added)->where('is_active', true)->get()
                ->filter(fn (BlockType $b) => $this->profileBlocks->isEligibleForScope($b, $type))
                ->pluck('key');
            $rejected = $added->reject(fn (string $k) => $eligible->contains($k));
            if ($rejected->isNotEmpty()) {
                throw new InvalidArgumentException(__('These blocks are not eligible for this skill: :keys', ['keys' => $rejected->implode(', ')]));
            }
        }

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
     * Add a skill to the catalog (no code change needed).
     *
     * @param  array<string, mixed>  $data
     */
    public function addSkill(User $admin, array $data): TalentType
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
