<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrite legacy FQCN morph values to the enforced aliases (ADR-F1).
 *
 * Before Phase 5 no morph map existed, so every morph column stored the model's
 * FQCN (e.g. "App\Models\Talent"). Relation::enforceMorphMap now writes aliases
 * ("talent"); this migration converts the already-stored values so type-filtered
 * queries (spatie/laravel-permission model_has_roles, activity log causers,
 * medialibrary owners, sanctum tokenables, contract morphs) keep matching.
 *
 * The FQCN → alias pairs are INLINED (not read from App\Support\MorphMap) so
 * this migration stays correct even if the map class evolves later.
 */
return new class extends Migration
{
    /**
     * Every (table, column) pair that held FQCN morph values before Phase 5.
     *
     * @var array<string, list<string>>
     */
    private array $columns = [
        'media' => ['model_type'],
        'activity_log' => ['subject_type', 'causer_type'],
        'model_has_roles' => ['model_type'],
        'model_has_permissions' => ['model_type'],
        'personal_access_tokens' => ['tokenable_type'],
        'contract_steps' => ['completed_by_type'],
        'contract_messages' => ['sender_type'],
    ];

    /**
     * FQCN → alias for every class that can have been stored pre-Phase 5.
     *
     * @var array<string, string>
     */
    private array $aliases = [
        'App\Models\Talent' => 'talent',
        'App\Models\Brand' => 'brand',
        'App\Models\User' => 'user',
        'App\Models\TalentType' => 'talent_type',
        'App\Models\BlockType' => 'block_type',
        'App\Models\ProfileBlock' => 'profile_block',
        'App\Models\PortfolioItem' => 'portfolio_item',
        'App\Models\BrandCollab' => 'brand_collab',
        'App\Models\Review' => 'review',
        'App\Models\Project' => 'project',
        'App\Models\Contract' => 'contract',
        'App\Models\ContractEnquiry' => 'contract_enquiry',
        'App\Models\ContractFlow' => 'contract_flow',
        'App\Models\ContractFlowStep' => 'contract_flow_step',
        'App\Models\ContractStep' => 'contract_step',
        'App\Models\ContractMessage' => 'contract_message',
        'App\Models\BrandProject' => 'brand_project',
        'App\Models\BrandProjectMedia' => 'brand_project_media',
        'App\Models\BrandReview' => 'brand_review',
        'App\Models\BrandImage' => 'brand_image',
        'App\Models\Digital' => 'digital',
        'App\Models\Showreel' => 'showreel',
        'Spatie\MediaLibrary\MediaCollections\Models\Media' => 'media',
        'Spatie\Permission\Models\Role' => 'role',
        'Spatie\Permission\Models\Permission' => 'permission',
    ];

    public function up(): void
    {
        $this->convert($this->aliases);
    }

    public function down(): void
    {
        $this->convert(array_flip($this->aliases));
    }

    /**
     * Apply a from → to value map to every registered morph column, skipping
     * tables that don't exist (test databases mid-refresh).
     *
     * @param  array<string, string>  $map
     */
    private function convert(array $map): void
    {
        foreach ($this->columns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                foreach ($map as $from => $to) {
                    DB::table($table)->where($column, $from)->update([$column => $to]);
                }
            }
        }
    }
};
