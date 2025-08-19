<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $config = [
            'from_phone_number_id' => $this->app->make('config')->get('services.whatsapp.from_phone_number_id'),
            'access_token' => $this->app->make('config')->get('services.whatsapp.token')
        ];

        $this->app->bind(WhatsAppCloudApi::class, static fn () => new WhatsAppCloudApi($config));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
