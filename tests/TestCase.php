<?php

namespace Tests;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    public function disableCookieEncryption(): void
    {
        $this->withoutMiddleware(EncryptCookies::class);
    }
}
