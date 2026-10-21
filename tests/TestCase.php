<?php

declare(strict_types=1);

namespace WojtJanowski\GlitchTip\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WojtJanowski\GlitchTip\GlitchTipServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [GlitchTipServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('glitchtip.url', 'https://glitchtip.test');
        $app['config']->set('glitchtip.token', 'test-token');
        $app['config']->set('glitchtip.organization', 'acme');
        $app['config']->set('glitchtip.retry.sleep_milliseconds', 0);
    }
}
