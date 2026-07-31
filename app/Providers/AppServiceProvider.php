<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Runtime;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        $this->keepSettingsFreshBetweenDispatches();
    }

    /**
     * Podešavanja se moraju ponovo pročitati na svakom zahtjevu.
     *
     * Na uređaju NativePHP servira sve zahtjeve iz jednog PHP procesa i između njih
     * poziva `Runtime::reset()`, koje čisti fasade ali **ne** i `scoped` veze. Sva
     * podešavanja (spatie ih registruje kao `scoped`) time postaju singletoni za
     * cijeli život procesa: sačuvaš izmjenu, a aplikacija do restarta i dalje čita
     * staru vrijednost. Zbog toga izbor modula u meniju „nije radio", a isto je
     * važilo za naziv kompanije, fiskalne parametre i sve ostalo.
     *
     * Obični Laravel ovo radi sam u `Application::handleRequest()`.
     */
    private function keepSettingsFreshBetweenDispatches(): void
    {
        if (! class_exists(Runtime::class)) {
            return;
        }

        Runtime::onReset(fn ($app) => $app->forgetScopedInstances());
    }
}
