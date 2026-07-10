<?php

use App\Http\Controllers\Api\V1\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Api\V1\Admin\OverviewController as AdminOverviewController;
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\BrandAuthController;
use App\Http\Controllers\Api\V1\Auth\TalentAuthController;
use App\Http\Controllers\Api\V1\Brand\AccountController as BrandAccountController;
use App\Http\Controllers\Api\V1\Brand\CampaignController as BrandCampaignController;
use App\Http\Controllers\Api\V1\Brand\CreativeNeedsController as BrandCreativeNeedsController;
use App\Http\Controllers\Api\V1\Brand\CredibilityController as BrandCredibilityController;
use App\Http\Controllers\Api\V1\Brand\DealController as BrandDealController;
use App\Http\Controllers\Api\V1\Brand\DiscoveryController as BrandDiscoveryController;
use App\Http\Controllers\Api\V1\Brand\EnquiryController as BrandEnquiryController;
use App\Http\Controllers\Api\V1\Brand\OnboardingController as BrandOnboardingController;
use App\Http\Controllers\Api\V1\Brand\ProfileController as BrandProfileController;
use App\Http\Controllers\Api\V1\Brand\ReviewController as BrandReviewController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\DealController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Talent\AccountController as TalentAccountController;
use App\Http\Controllers\Api\V1\Talent\AffiliationController as TalentAffiliationController;
use App\Http\Controllers\Api\V1\Talent\AvailabilityController as TalentAvailabilityController;
use App\Http\Controllers\Api\V1\Talent\CompCardController as TalentCompCardController;
use App\Http\Controllers\Api\V1\Talent\ContentController as TalentContentController;
use App\Http\Controllers\Api\V1\Talent\DealController as TalentDealController;
use App\Http\Controllers\Api\V1\Talent\EnquiryController as TalentEnquiryController;
use App\Http\Controllers\Api\V1\Talent\PressController as TalentPressController;
use App\Http\Controllers\Api\V1\Talent\ProfessionController as TalentProfessionController;
use App\Http\Controllers\Api\V1\Talent\ProfileController as TalentProfileController;
use App\Http\Controllers\Api\V1\Talent\ReviewController as TalentReviewController;
use App\Http\Controllers\Api\V1\Talent\ServiceController as TalentServiceController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fama mobile API — v1
|--------------------------------------------------------------------------
|
| Registered under the `api` prefix by bootstrap/app.php, so every path here
| resolves at /api/v1/... . The whole surface is stateless Sanctum token auth
| with the shared JSON envelope; `SetApiLocale` negotiates the response locale
| from Accept-Language, and every route is throttled (a stricter bucket guards
| the credential endpoints). New versions get their own prefix group — the v1
| contract never shifts under existing clients.
|
*/

Route::prefix('v1')
    ->middleware([SetApiLocale::class, 'throttle:api'])
    ->group(function () {
        // ---- Talent authentication -----------------------------------------
        Route::prefix('talent')->group(function () {
            Route::post('register', [TalentAuthController::class, 'register'])->middleware('throttle:auth');
            Route::post('login', [TalentAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:talent'])->group(function () {
                Route::get('me', [TalentAuthController::class, 'me']);
                Route::post('refresh', [TalentAuthController::class, 'refresh']);
                Route::post('logout', [TalentAuthController::class, 'logout']);
            });
        });

        // ---- Brand authentication ------------------------------------------
        Route::prefix('brand')->group(function () {
            Route::post('register', [BrandAuthController::class, 'register'])->middleware('throttle:auth');
            Route::post('login', [BrandAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:brand'])->group(function () {
                Route::get('me', [BrandAuthController::class, 'me']);
                Route::post('refresh', [BrandAuthController::class, 'refresh']);
                Route::post('logout', [BrandAuthController::class, 'logout']);
            });
        });

        // ---- Admin authentication ------------------------------------------
        // No public admin sign-up — staff are provisioned by an existing admin
        // (see AdminAuthController::register, gated by manage-users).
        Route::prefix('admin')->group(function () {
            Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
                Route::get('me', [AdminAuthController::class, 'me']);
                Route::post('refresh', [AdminAuthController::class, 'refresh']);
                Route::post('logout', [AdminAuthController::class, 'logout']);
                Route::post('register', [AdminAuthController::class, 'register'])->middleware('abilities:manage-users');

                // Admin-lite reads (heavy admin stays on web).
                Route::get('overview', [AdminOverviewController::class, 'index']);
                Route::get('activity', [AdminActivityController::class, 'index'])->middleware('abilities:manage-settings');
            });
        });

        // ---- Public discovery + profile interactions (read + public writes) -
        Route::get('talents', [TalentController::class, 'index']);
        Route::get('talents/{talent:slug}', [TalentController::class, 'show']);
        Route::get('talents/{talent:slug}/projects/{project}', [TalentController::class, 'project']);
        Route::post('talents/{talent:slug}/reviews', [TalentController::class, 'submitReview']);
        Route::post('talents/{talent:slug}/enquiries', [TalentController::class, 'submitEnquiry']);
        Route::get('brands', [BrandController::class, 'index']);
        Route::get('brands/{brand:slug}', [BrandController::class, 'show']);
        Route::get('brands/{brand:slug}/campaigns/{campaign:slug}', [BrandController::class, 'campaign'])->scopeBindings();

        // ---- Reference / lookups (public catalog for dynamic UI) -----------
        Route::get('lookups/talent-types', [LookupController::class, 'talentTypes']);
        Route::get('lookups/block-types', [LookupController::class, 'blockTypes']);
        Route::get('lookups/deal-flows', [LookupController::class, 'dealFlows']);
        Route::get('lookups/options', [LookupController::class, 'options']);

        // ---- Authenticated deal inbox (talent or brand token) --------------
        Route::middleware(['auth:sanctum', 'ability:talent,brand'])->group(function () {
            Route::get('deals', [DealController::class, 'index']);
            Route::get('deals/{deal}', [DealController::class, 'show']);

            // Notifications (deal turn changes + new messages).
            Route::get('notifications', [NotificationController::class, 'index']);
            Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
            Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
        });

        // ---- Talent workspace (talent token) -------------------------------
        // The full talent management surface — thin controllers over the same
        // services the web dashboard uses. `reorder` routes precede `{id}` so the
        // literal segment is never captured as an id.
        Route::prefix('talent')
            ->middleware(['auth:sanctum', 'abilities:talent'])
            ->group(function () {
                // Profile core + hero + blocks.
                Route::get('profile', [TalentProfileController::class, 'show']);
                Route::patch('profile', [TalentProfileController::class, 'update']);
                Route::post('profile/hero', [TalentProfileController::class, 'uploadHero']);
                Route::get('profile/blocks', [TalentProfileController::class, 'blocks']);
                Route::get('profile/block-picker', [TalentProfileController::class, 'picker']);
                Route::post('profile/blocks', [TalentProfileController::class, 'addBlock']);
                Route::patch('profile/blocks/reorder', [TalentProfileController::class, 'reorderBlocks']);
                Route::patch('profile/blocks/{block}', [TalentProfileController::class, 'fillBlock']);
                Route::patch('profile/blocks/{block}/visibility', [TalentProfileController::class, 'toggleBlock']);
                Route::delete('profile/blocks/{block}', [TalentProfileController::class, 'removeBlock']);

                // Professions.
                Route::get('professions', [TalentProfessionController::class, 'index']);
                Route::post('professions', [TalentProfessionController::class, 'store']);
                Route::patch('professions/reorder', [TalentProfessionController::class, 'reorder']);
                Route::patch('professions/{type}/primary', [TalentProfessionController::class, 'primary']);
                Route::delete('professions/{type}', [TalentProfessionController::class, 'destroy']);

                // Comp card (1:1).
                Route::get('comp-card', [TalentCompCardController::class, 'show']);
                Route::put('comp-card', [TalentCompCardController::class, 'update']);
                Route::delete('comp-card', [TalentCompCardController::class, 'destroy']);

                // Services / rate card.
                Route::get('services', [TalentServiceController::class, 'index']);
                Route::post('services', [TalentServiceController::class, 'store']);
                Route::patch('services/{service}', [TalentServiceController::class, 'update']);
                Route::patch('services/{service}/toggle', [TalentServiceController::class, 'toggle']);
                Route::delete('services/{service}', [TalentServiceController::class, 'destroy']);

                // Availability & travel.
                Route::get('availability', [TalentAvailabilityController::class, 'show']);
                Route::patch('availability', [TalentAvailabilityController::class, 'update']);

                // Reviews moderation.
                Route::get('reviews', [TalentReviewController::class, 'index']);
                Route::patch('reviews/{review}/approve', [TalentReviewController::class, 'approve']);
                Route::patch('reviews/{review}/reject', [TalentReviewController::class, 'reject']);

                // Affiliations.
                Route::get('affiliations', [TalentAffiliationController::class, 'index']);
                Route::post('affiliations', [TalentAffiliationController::class, 'store']);
                Route::patch('affiliations/{affiliation}', [TalentAffiliationController::class, 'update']);
                Route::patch('affiliations/{affiliation}/end', [TalentAffiliationController::class, 'end']);
                Route::delete('affiliations/{affiliation}', [TalentAffiliationController::class, 'destroy']);

                // Press.
                Route::get('press', [TalentPressController::class, 'index']);
                Route::post('press', [TalentPressController::class, 'store']);
                Route::delete('press/{press}', [TalentPressController::class, 'destroy']);

                // Account.
                Route::get('account', [TalentAccountController::class, 'show']);
                Route::patch('account', [TalentAccountController::class, 'update']);
                Route::patch('account/publish', [TalentAccountController::class, 'publish']);

                // Deals — inbox, room, step actions.
                Route::get('deals', [TalentDealController::class, 'index']);
                Route::get('deals/{deal}', [TalentDealController::class, 'show']);
                Route::post('deals/{deal}/advance', [TalentDealController::class, 'advance']);
                Route::post('deals/{deal}/reject', [TalentDealController::class, 'reject']);
                Route::post('deals/{deal}/skip', [TalentDealController::class, 'skip']);
                Route::post('deals/{deal}/message', [TalentDealController::class, 'message']);

                // Enquiries (incoming, read-only).
                Route::get('enquiries', [TalentEnquiryController::class, 'index']);
                Route::get('enquiries/{enquiry}', [TalentEnquiryController::class, 'show']);

                // Content child tables (registry-driven; reorder precedes {id}).
                Route::get('content/{type}', [TalentContentController::class, 'index']);
                Route::post('content/{type}', [TalentContentController::class, 'store']);
                Route::patch('content/{type}/reorder', [TalentContentController::class, 'reorder']);
                Route::post('content/{type}/{id}/media', [TalentContentController::class, 'uploadMedia']);
                Route::patch('content/{type}/{id}', [TalentContentController::class, 'update']);
                Route::delete('content/{type}/{id}', [TalentContentController::class, 'destroy']);
            });

        // ---- Brand workspace (brand token) ---------------------------------
        // The full brand management surface — thin controllers over the same
        // services the web dashboard uses.
        Route::prefix('brand')
            ->middleware(['auth:sanctum', 'abilities:brand'])
            ->group(function () {
                // Onboarding (6 steps).
                Route::get('onboarding', [BrandOnboardingController::class, 'status']);
                Route::post('onboarding/identity', [BrandOnboardingController::class, 'identity']);
                Route::post('onboarding/location', [BrandOnboardingController::class, 'location']);
                Route::post('onboarding/creative-needs', [BrandOnboardingController::class, 'creativeNeeds']);
                Route::post('onboarding/aesthetic', [BrandOnboardingController::class, 'aesthetic']);
                Route::post('onboarding/budget', [BrandOnboardingController::class, 'budget']);
                Route::post('onboarding/complete', [BrandOnboardingController::class, 'complete']);

                // Profile core + media + aesthetic + images + social.
                Route::get('profile', [BrandProfileController::class, 'show']);
                Route::patch('profile', [BrandProfileController::class, 'update']);
                Route::post('profile/logo', [BrandProfileController::class, 'uploadLogo']);
                Route::post('profile/cover', [BrandProfileController::class, 'uploadCover']);
                Route::patch('profile/aesthetic', [BrandProfileController::class, 'updateAesthetic']);
                Route::get('profile/images', [BrandProfileController::class, 'images']);
                Route::post('profile/images', [BrandProfileController::class, 'addImage']);
                Route::delete('profile/images/{image}', [BrandProfileController::class, 'removeImage']);
                Route::get('social', [BrandProfileController::class, 'social']);
                Route::post('social', [BrandProfileController::class, 'addSocial']);
                Route::delete('social/{handle}', [BrandProfileController::class, 'removeSocial']);

                // Creative needs.
                Route::get('creative-needs', [BrandCreativeNeedsController::class, 'show']);
                Route::patch('creative-needs', [BrandCreativeNeedsController::class, 'update']);

                // Campaigns (roles + media + status; single-campaign deals).
                Route::get('campaigns', [BrandCampaignController::class, 'index']);
                Route::post('campaigns', [BrandCampaignController::class, 'store']);
                Route::get('campaigns/{campaign}', [BrandCampaignController::class, 'show']);
                Route::patch('campaigns/{campaign}', [BrandCampaignController::class, 'update']);
                Route::patch('campaigns/{campaign}/status', [BrandCampaignController::class, 'status']);
                Route::patch('campaigns/{campaign}/public', [BrandCampaignController::class, 'setPublic']);
                Route::post('campaigns/{campaign}/media', [BrandCampaignController::class, 'addMedia']);
                Route::delete('campaigns/{campaign}', [BrandCampaignController::class, 'destroy']);

                // Discovery feed + signal writes.
                Route::get('discover', [BrandDiscoveryController::class, 'feed']);
                Route::post('discover/save', [BrandDiscoveryController::class, 'save']);
                Route::post('discover/brief', [BrandDiscoveryController::class, 'brief']);

                // Deal initiation (Path A) + pending enquiries (Path B).
                Route::post('deals', [BrandDealController::class, 'store']);
                Route::get('enquiries', [BrandEnquiryController::class, 'index']);
                Route::post('enquiries/{enquiry}/convert', [BrandEnquiryController::class, 'convert']);

                // Deals — inbox, room, step actions.
                Route::get('deals', [BrandDealController::class, 'index']);
                Route::get('deals/{deal}', [BrandDealController::class, 'show']);
                Route::post('deals/{deal}/advance', [BrandDealController::class, 'advance']);
                Route::post('deals/{deal}/reject', [BrandDealController::class, 'reject']);
                Route::post('deals/{deal}/skip', [BrandDealController::class, 'skip']);
                Route::post('deals/{deal}/message', [BrandDealController::class, 'message']);

                // Reviews received (read-only) + credibility (read).
                Route::get('reviews', [BrandReviewController::class, 'index']);
                Route::get('credibility', [BrandCredibilityController::class, 'show']);

                // Account / settings-stage fields + publish.
                Route::get('account', [BrandAccountController::class, 'show']);
                Route::patch('account', [BrandAccountController::class, 'update']);
                Route::patch('account/publish', [BrandAccountController::class, 'publish']);
            });
    });
