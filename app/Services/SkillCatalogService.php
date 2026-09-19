<?php

namespace App\Services;

use App\Models\BlockType;
use App\Models\Speciality;
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

    /**
     * Add a speciality under a skill (ADR-Y). This is the ONLY way an entry
     * enters the controlled vocabulary — talents pick, they never type — so the
     * governance lives here with the rest of the catalog.
     *
     * The slug is unique per skill, not globally: "fashion" legitimately exists
     * under photography, modelling and styling, and that overlap is what lets
     * one search assemble a whole crew.
     *
     * @param  array<string, mixed>  $data
     */
    public function addSpeciality(User $admin, TalentType $type, array $data): Speciality
    {
        $this->authorizeAdmin($admin, 'manage', $type);

        $slug = $data['slug'] ?? Str::slug(is_array($data['name']) ? ($data['name']['en'] ?? reset($data['name'])) : $data['name']);

        if ($type->specialities()->where('slug', $slug)->exists()) {
            throw new InvalidArgumentException(__('This skill already has a speciality called ":slug".', ['slug' => $slug]));
        }

        return $this->runInTransaction(function () use ($admin, $type, $data, $slug): Speciality {
            $speciality = $type->specialities()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_regional' => (bool) ($data['is_regional'] ?? false),
                'is_active' => true,
                'position' => (int) ($type->specialities()->max('position') + 1),
            ]);

            $this->record($admin, $speciality, 'catalog', 'speciality.created', [
                'skill' => $type->slug, 'slug' => $speciality->slug,
            ]);

            return $speciality;
        }, ['talent_type_id' => $type->getKey(), 'slug' => $slug]);
    }

    /**
     * Edit a speciality's copy or its regional flag. The SLUG IS LOCKED once the
     * entry exists — it is the stable handle the vocabulary is filtered on, and
     * renaming it would silently drop every talent and project already matched
     * through it. Retire and re-add instead.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSpeciality(User $admin, Speciality $speciality, array $data): Speciality
    {
        $this->authorizeAdmin($admin, 'manage', $speciality->talentType);

        return $this->runInTransaction(function () use ($admin, $speciality, $data): Speciality {
            $speciality->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'is_regional' => $data['is_regional'] ?? null,
            ], fn ($value) => $value !== null));

            $this->record($admin, $speciality, 'catalog', 'speciality.updated', [
                'slug' => $speciality->slug,
            ]);

            return $speciality->refresh();
        }, ['speciality_id' => $speciality->getKey()]);
    }

    /**
     * Retire or reinstate a speciality — the grandfathering toggle (ADR-T's rule,
     * applied to the vocabulary). A retired entry vanishes from every picker
     * while the talents and projects that already claim it keep rendering and
     * keep being found. This is deliberately NOT a delete: deleting would cascade
     * those claims away and rewrite history.
     */
    public function setSpecialityActive(User $admin, Speciality $speciality, bool $active): Speciality
    {
        $this->authorizeAdmin($admin, 'manage', $speciality->talentType);

        return $this->runInTransaction(function () use ($admin, $speciality, $active): Speciality {
            $speciality->update(['is_active' => $active]);

            $this->record($admin, $speciality, 'catalog', $active ? 'speciality.reinstated' : 'speciality.retired', [
                'slug' => $speciality->slug,
            ]);

            return $speciality->refresh();
        }, ['speciality_id' => $speciality->getKey(), 'active' => $active]);
    }

    /**
     * Reorder a skill's specialities. The catalog order is editorial — it is what
     * the picker and the public profile render — so it is worth persisting rather
     * than sorting alphabetically at read time.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderSpecialities(User $admin, TalentType $type, array $orderedIds): void
    {
        $this->authorizeAdmin($admin, 'manage', $type);

        $this->runInTransaction(function () use ($type, $orderedIds): void {
            $owned = $type->specialities()->pluck('id')->map('intval')->all();

            foreach ($orderedIds as $position => $id) {
                if (! in_array((int) $id, $owned, true)) {
                    throw new InvalidArgumentException("Speciality [{$id}] does not belong to [{$type->slug}].");
                }

                Speciality::whereKey($id)->update(['position' => $position]);
            }
        }, ['talent_type_id' => $type->getKey()]);
    }
}
