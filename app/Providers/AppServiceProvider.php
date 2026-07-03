<?php
namespace App\Providers;
use App\Services\Sms\Gateway\FakeGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Tenancy\TenantContext;
use App\Services\Theme\ThemeManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(\App\Services\Platform\ImpersonationService::class);
        $this->app->bind(SmsGateway::class, fn () => match (config('sms.driver')) {
            default => new FakeGateway,
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
