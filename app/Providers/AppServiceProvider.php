<?php

namespace App\Providers;

use App\Support\ApiResponse;
use App\Support\MorphMap;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services. CurrentActor is stateless (the
     * guards cache their users), so plain container auto-resolution serves it.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services: strict models in non-production and
     * the shared JSON-envelope response macros.
     */
    public function boot(): void
    {
        // ZeptoMail HTTPS API transport (MAIL_MAILER=zeptomail) — see App\Mail\Transport\ZeptoMailTransport.
        \Illuminate\Support\Facades\Mail::extend('zeptomail', fn (array $config) => new \App\Mail\Transport\ZeptoMailTransport(
            (string) ($config['token'] ?? ''),
            (string) ($config['host'] ?? 'api.zeptomail.com'),
            (int) ($config['timeout'] ?? 15),
        ));

        // Listeners in app/Listeners are auto-discovered by their handle() type-hint
        // (Laravel 11+): SyncStateProjections (StateChanged), IncrementProfileViewCount
        // (TalentProfileViewed), LogMediaUploaded (MediaHasBeenAddedEvent),
        // AdvancePortfolioMediaState (ConversionHasBeenCompletedEvent).
        $this->configureModels();
        $this->configurePasswordResetLinks();
        $this->enforceMorphMap();
        $this->registerResponseMacros();
        $this->configureFeedRateLimiters();

        // Release feature flags (config/features.php): @feature('brands') … @endfeature
        // (and @unlessfeature) gate brand/contracts/messaging UI in the V1 release.
        \Illuminate\Support\Facades\Blade::if('feature', fn (string $name) => \App\Support\Features::enabled($name));

        // Suggestion caches follow the viewer's own profile (5Z): an update
        // to it re-ranks their rails on the next request.
        \App\Models\Talent::updated(fn ($talent) => app(\App\Services\SuggestionService::class)->forget($talent));
        \App\Models\Brand::updated(fn ($brand) => app(\App\Services\SuggestionService::class)->forget($brand));
    }

    /**
     * Per-actor feed rate limits (feed-spec §12): keyed by the acting feed
     * identity when present, else the client IP. Numbers live here, names in
     * the routes.
     */
    private function configureFeedRateLimiters(): void
    {
        $key = function (\Illuminate\Http\Request $request): string {
            $actor = app(\App\Feed\CurrentActor::class)->resolve();

            return $actor !== null
                ? $actor->getMorphClass().':'.$actor->getKey()
                : 'ip:'.$request->ip();
        };

        \Illuminate\Support\Facades\RateLimiter::for('feed-posts', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perHour(10)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-comments', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-likes', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-follows', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-bookmarks', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-dismissals', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-links', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($key($request)));
        \Illuminate\Support\Facades\RateLimiter::for('feed-mentions', fn ($request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by($key($request)));
    }

    /**
     * Enforce the aliased morph map (ADR-F1): every morph column stores a
     * stable alias (talent, brand, post, …) instead of an FQCN, and any model
     * missing from App\Support\MorphMap throws instead of silently writing
     * its class name.
     */
    private function enforceMorphMap(): void
    {
        Relation::enforceMorphMap(MorphMap::map());
    }

    /**
     * Fail loudly in development so N+1 queries and typos in attribute names are
     * caught early; stay lenient in production.
     */
    private function configureModels(): void
    {
        $shouldBeStrict = ! $this->app->isProduction();

        Model::preventLazyLoading($shouldBeStrict);
        Model::preventSilentlyDiscardingAttributes($shouldBeStrict);
    }

    /**
     * Register response()->success()/error()/paginated() so every controller
     * (web-Ajax and API) returns the same { success, data, message, errors,
     * meta } envelope.
     */
    private function registerResponseMacros(): void
    {
        Response::macro('success', function (mixed $data = null, ?string $message = null, array $meta = [], int $status = 200) {
            return ApiResponse::success($data, $message, $meta, $status);
        });

        Response::macro('error', function (?string $message = null, ?array $errors = null, int $status = 400, mixed $data = null, array $meta = []) {
            return ApiResponse::error($message, $errors, $status, $data, $meta);
        });

        Response::macro('paginated', function (LengthAwarePaginator $paginator, mixed $data = null, ?string $message = null, array $meta = [], int $status = 200) {
            return ApiResponse::paginated($paginator, $data, $message, $meta, $status);
        });
    }

    /**
     * Reset links name the account type they were issued for (`account=talent|
     * brand|user`, the MorphMap alias) so the new-password step resets the same
     * account through the matching broker — one email can exist as several types.
     */
    private function configurePasswordResetLinks(): void
    {
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(
            fn ($notifiable, string $token) => route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
                'account' => $notifiable->getMorphClass(),
            ])
        );
    }
}
