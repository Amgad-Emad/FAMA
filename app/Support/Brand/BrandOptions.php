<?php

namespace App\Support\Brand;

/**
 * Single source of truth for the brand-side enum option lists (brand-spec).
 * These back the validation `Rule::in(...)` checks on both the web dashboard
 * (onboarding / profile / creative-needs / account controllers) and the mobile
 * API — keeping the allowed values in one place so the two surfaces never drift.
 */
final class BrandOptions
{
    /** @var list<string> */
    public const INDUSTRIES = ['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other'];

    /** @var list<string> */
    public const STAGES = ['new', 'growing', 'established'];

    /** @var list<string> */
    public const REACH = ['same_city', 'mena', 'international'];

    /** @var list<string> */
    public const FREQUENCY = ['occasional', 'monthly', 'weekly', 'ongoing'];

    /** @var list<string> */
    public const PROJECT_TYPES = ['editorial', 'lookbook', 'campaign_video', 'social_content', 'brand_identity'];

    /** @var list<string> */
    public const MOODS = ['editorial', 'minimal', 'bold', 'warm', 'dark', 'playful', 'luxurious', 'raw', 'nostalgic', 'commercial'];

    /** @var list<string> */
    public const BUDGETS = ['under_500', '500_2000', '2000_10000', '10000_plus'];

    /** @var list<string> */
    public const PLATFORMS = ['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'facebook', 'behance', 'website', 'other'];

    /** @var list<string> */
    public const COMPANY_SIZES = ['solo', 'small', 'medium', 'large', 'enterprise'];
}
