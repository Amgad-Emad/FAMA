<?php

namespace App\Providers;

use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
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
        $this->configureModels();
        $this->registerResponseMacros();
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
}
