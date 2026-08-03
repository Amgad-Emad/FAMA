<?php

namespace App\Support;

use App\Models;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The single source of truth for polymorphic morph aliases (ADR-F1).
 *
 * The feed stores actor/subject morphs under stable aliases (talent, brand,
 * user, post, comment, project, …) instead of FQCNs, and the map is ENFORCED —
 * every model that can appear in a morph column must be listed here, or
 * Eloquent throws a ClassMorphViolationException at write time. That is the
 * point: a rename of a model class can never silently corrupt morph data.
 *
 * Migration 2026_07_22_000200 rewrites pre-existing FQCN morph values
 * (media, activity_log, permission pivots, sanctum tokens, contract morphs)
 * to these aliases.
 */
class MorphMap
{
    /**
     * Return the complete alias → class morph map. Aliases are the singular
     * snake_case of the model name and are frozen — changing one is a data
     * migration, not an edit.
     *
     * @return array<string, class-string>
     */
    public static function map(): array
    {
        return [
            // Actors & platform
            'talent' => Models\Talent::class,
            'brand' => Models\Brand::class,
            'user' => Models\User::class,

            // Feed (Phase 5)
            'post' => Models\Post::class,
            'comment' => Models\Comment::class,
            'like' => Models\Like::class,
            'follow' => Models\Follow::class,
            'mention' => Models\Mention::class,
            'link_preview' => Models\LinkPreview::class,
            'linked_url' => Models\LinkedUrl::class,
            'post_report' => Models\PostReport::class,
            'feed_seen' => Models\FeedSeen::class,
            'bookmark' => Models\Bookmark::class,
            'suggestion_dismissal' => Models\SuggestionDismissal::class,

            // Talent domain
            'talent_type' => Models\TalentType::class,
            'block_type' => Models\BlockType::class,
            'block_type_category' => Models\BlockTypeCategory::class,
            'profile_block' => Models\ProfileBlock::class,
            'portfolio_item' => Models\PortfolioItem::class,
            'brand_collab' => Models\BrandCollab::class,
            'review' => Models\Review::class,
            'comp_card' => Models\CompCard::class,
            'look_type' => Models\LookType::class,
            'digital' => Models\Digital::class,
            'showreel' => Models\Showreel::class,
            'equipment' => Models\Equipment::class,
            'project' => Models\Project::class,
            'software_stack' => Models\SoftwareStack::class,

            // Contract engine
            'contract' => Models\Contract::class,
            'contract_enquiry' => Models\ContractEnquiry::class,
            'contract_flow' => Models\ContractFlow::class,
            'contract_flow_step' => Models\ContractFlowStep::class,
            'contract_step' => Models\ContractStep::class,
            'contract_message' => Models\ContractMessage::class,

            // Brand domain
            'brand_aesthetic' => Models\BrandAesthetic::class,
            'brand_creative_need' => Models\BrandCreativeNeed::class,
            'brand_credibility' => Models\BrandCredibility::class,
            'brand_image' => Models\BrandImage::class,
            'brand_mood_tag' => Models\BrandMoodTag::class,
            'brand_project' => Models\BrandProject::class,
            'brand_project_media' => Models\BrandProjectMedia::class,
            'brand_project_type' => Models\BrandProjectType::class,
            'brand_review' => Models\BrandReview::class,
            'brand_signal' => Models\BrandSignal::class,
            'brand_social_handle' => Models\BrandSocialHandle::class,

            // Platform / packages
            'setting' => Models\Setting::class,
            'media' => Media::class,
            'role' => Role::class,
            'permission' => Permission::class,
        ];
    }
}
