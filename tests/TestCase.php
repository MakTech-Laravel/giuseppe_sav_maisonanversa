<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        URL::defaults(['locale' => config('maison.default_locale')]);

        /*
         * Keep auth/locale redirects deterministic. Preference tests override
         * Accept-Language explicitly when negotiating browser language.
         */
        $this->withHeader('Accept-Language', config('maison.default_locale'));
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
