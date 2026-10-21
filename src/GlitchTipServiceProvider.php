<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use WojtJanowski\GlitchTip\Support\Connector;

final class GlitchTipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/glitchtip.php', 'glitchtip');

        $this->app->singleton(GlitchTip::class, function (Application $app): GlitchTip {
            /** @var array{url?: string|null, token?: string|null, organization?: string|null, timeout?: int, connect_timeout?: int, retry?: array{times?: int, sleep_milliseconds?: int}} $config */
            $config = $app['config']->get('glitchtip', []);

            return new GlitchTip(
                connector: new Connector(
                    http: $app->make(Factory::class),
                    url: (string) ($config['url'] ?? 'https://app.glitchtip.com'),
                    token: $config['token'] ?? null,
                    timeout: $config['timeout'] ?? 30,
                    connectTimeout: $config['connect_timeout'] ?? 10,
                    retryTimes: $config['retry']['times'] ?? 2,
                    retrySleepMilliseconds: $config['retry']['sleep_milliseconds'] ?? 500,
                ),
                organization: $config['organization'] ?? null,
            );
        });

        $this->app->alias(GlitchTip::class, 'glitchtip');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/glitchtip.php' => config_path('glitchtip.php'),
            ], 'glitchtip-config');
        }
    }
}
