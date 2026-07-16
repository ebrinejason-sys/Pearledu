<?php
namespace App\Providers;
use App\Services\Sms\Gateway\FakeGateway;
use App\Services\Sms\Gateway\LogGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Sms\Gateway\UnconfiguredGateway;
use App\Services\Tenancy\TenantContext;
use App\Services\Theme\ThemeManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(\App\Services\Platform\ImpersonationService::class);
        $this->app->bind(SmsGateway::class, function () {
            $driver = (string) config('sms.driver', 'fake');

            return match ($driver) {
                'fake' => new FakeGateway,
                'log' => new LogGateway,
                default => new UnconfiguredGateway($driver),
            };
        });
    }
    public function boot(): void {
        View::composer('*', function ($view) {
            $view->with('themeCss', app(ThemeManager::class)->cssVariables());
        });
        View::composer(['layouts.app', 'layouts.partials.topbar', 'layouts.partials.sidebar'], function ($view) {
            if (auth()->check()) {
                $view->with('nav', app(\App\Services\Navigation\NavigationBuilder::class)->build(auth()->user()));
            }
        });
    }
}
