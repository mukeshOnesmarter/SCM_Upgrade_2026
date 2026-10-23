<?php

namespace App\Providers;

use App\Services\GeneralSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Mailjet\Transport\MailjetApiTransport;

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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Email goes through Mailjet's Send API (not SMTP).
        Mail::extend('mailjet', fn (array $config) => new MailjetApiTransport(
            (string) $config['key'],
            (string) $config['secret'],
            HttpClient::create(),
        ));

        // SCM's fixed growth and demand settings, shown on several pages.
        View::share('settings', app(GeneralSettingsService::class)->generalSettings());

        if ($this->app->environment('production', 'staging')) {
            // Every link the app writes uses https, even behind the host's proxy.
            URL::forceScheme('https');

            // migrate:fresh, db:wipe and similar refuse to run on a live database.
            DB::prohibitDestructiveCommands();
        }
    }
}
