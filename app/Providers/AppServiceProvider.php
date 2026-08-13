<?php

namespace App\Providers;

use App\Services\Navigation\NavigationBuilder;
use App\Services\Platform\ImpersonationService;
use App\Services\Sms\Gateway\FakeGateway;
use App\Services\Sms\Gateway\LogGateway;
use App\Services\Sms\Gateway\ProductionBlockedGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Sms\Gateway\TwilioGateway;
use App\Services\Sms\Gateway\UnconfiguredGateway;
use App\Services\Tenancy\TenantContext;
use App\Services\Theme\ThemeManager;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(ImpersonationService::class);
        $this->app->bind(SmsGateway::class, function () {
            $driver = (string) config('sms.driver', 'fake');

            if ($this->app->environment('production') && in_array($driver, ['fake', 'log'], true)) {
                return new ProductionBlockedGateway($driver);
            }

            return match ($driver) {
                'fake' => new FakeGateway,
                'log' => new LogGateway,
                'twilio' => $this->twilioGateway(),
                default => new UnconfiguredGateway($driver),
            };
        });
    }

    private function twilioGateway(): SmsGateway
    {
        $sid = (string) config('sms.twilio.sid');
        $token = (string) config('sms.twilio.token');
        if ($sid === '' || $token === '') {
            return new UnconfiguredGateway('twilio (missing TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN)');
        }

        return new TwilioGateway($sid, $token, config('sms.twilio.from'));
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            config(['session.secure' => true]);

            if (config('app.debug')) {
                report(new \RuntimeException('APP_DEBUG=true in production — disable immediately.'));
            }
        }

        Password::defaults(static fn () => Password::min(10));

        View::composer('*', function ($view) {
            $theme = app(ThemeManager::class);
            $view->with([
                'themeCss' => $theme->cssVariables(),
                'themeFontUrl' => $theme->fontUrl(),
                'themeColor' => $theme->themeColor(),
            ]);
        });
        View::composer(['layouts.app', 'layouts.partials.topbar', 'layouts.partials.sidebar'], function ($view) {
            if (auth()->check()) {
                $view->with('nav', app(NavigationBuilder::class)->build(auth()->user()));
            }
        });
    }
}
