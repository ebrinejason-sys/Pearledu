<?php
namespace App\Providers;
use App\Services\Sms\Gateway\FakeGateway;
use App\Services\Sms\Gateway\LogGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Sms\Gateway\TwilioGateway;
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
                'twilio' => $this->twilioGateway(),
                default => new UnconfiguredGateway($driver),
            };
        });
    }
    private function twilioGateway(): SmsGateway {
        $sid = (string) config('sms.twilio.sid');
        $token = (string) config('sms.twilio.token');
        if ($sid === '' || $token === '') {
            return new UnconfiguredGateway('twilio (missing TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN)');
        }
        return new TwilioGateway($sid, $token, config('sms.twilio.from'));
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
