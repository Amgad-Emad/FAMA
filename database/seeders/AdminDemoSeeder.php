<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\DealFlow;
use App\Models\Review;
use App\Models\Talent;
use App\Models\User;
use App\Services\BrandModerationService;
use App\Services\DealFlowBuilderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Admin demo data (Phase 3B): a couple of extra deal flows authored by the demo
 * super-admin (which generates real audit entries), plus items sitting in the
 * moderation queues. Runs after the demo talent/brand + RBAC seeders. Idempotent.
 */
class AdminDemoSeeder extends Seeder
{
    public function run(DealFlowBuilderService $flows): void
    {
        $admin = User::query()->where('email', 'test@example.com')->first();
        if ($admin === null) {
            return;
        }

        // Authenticate the admin so flow/step LogsActivity records the causer.
        Auth::guard('admin')->setUser($admin);

        if (! DealFlow::query()->where('slug', 'like', 'quick-shoot%')->exists()) {
            $draft = $flows->createFlow($admin, ['name' => 'Quick Shoot', 'applies_to' => 'model', 'description' => 'A lightweight 3-step booking.']);
            $flows->addStep($admin, $draft, ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'settings' => ['fields' => ['scope', 'budget']]]);
            $flows->addStep($admin, $draft, ['key' => 'confirm', 'name' => 'Confirm', 'actor' => 'talent', 'step_type' => 'approval']);
            $flows->addStep($admin, $draft, ['key' => 'wrap', 'name' => 'Wrap', 'actor' => 'system', 'step_type' => 'info']);

            $premium = $flows->createFlow($admin, ['name' => 'Premium Booking', 'applies_to' => 'creative']);
            $flows->addStep($admin, $premium, ['key' => 'brief', 'name' => 'Creative brief', 'actor' => 'brand', 'step_type' => 'form']);
            $flows->addStep($admin, $premium, ['key' => 'contract', 'name' => 'Contract', 'actor' => 'both', 'step_type' => 'contract']);
            $flows->activate($admin, $premium);

            // Model events are muted during seeding (WithoutModelEvents), so the
            // flow LogsActivity trait won't fire — record the authoring explicitly
            // so the audit viewer has real entries to show.
            activity('deal_flow')->causedBy($admin)->performedOn($draft)->log('created');
            activity('deal_flow')->causedBy($admin)->performedOn($premium)->log('activated');
        }

        // Items pending moderation (queues have something to show).
        $talent = Talent::query()->where('slug', 'demo-talent')->first();
        if ($talent !== null && $talent->reviews()->where('status', 'pending')->doesntExist()) {
            Review::factory()->count(2)->pending()->create(['talent_id' => $talent->id]);
        }

        $brand = Brand::query()->where('slug', 'nomad-coffee')->first();
        if ($brand !== null && $talent !== null && $brand->brandReviews()->where('status', 'pending')->doesntExist()) {
            BrandReview::factory()->pending()->create(['brand_id' => $brand->id, 'talent_id' => $talent->id]);
        }

        // A real (explicitly-logged) moderation action for the audit trail.
        if ($brand !== null) {
            app(BrandModerationService::class)->verify($admin, $brand);
        }

        Auth::guard('admin')->logout();
    }
}
