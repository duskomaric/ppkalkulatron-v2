<?php

namespace App\Providers;

use App\Services\FiscalDeviceHealth;
use App\Services\PinLock;
use App\Settings\CompanySettings;
use App\Settings\UserSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Vite;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Native\Mobile\Runtime;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useTailwind();
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->shareLayoutData();
        $this->keepSettingsFreshBetweenDispatches();
    }

    private function shareLayoutData(): void
    {
        View::composer('layouts.app', function (ViewInstance $view): void {
            $pinLock = $this->app->make(PinLock::class);

            $view->with('autoLockMinutes', $pinLock->isEnabled() ? $pinLock->autoLockMinutes() : 0);
        });

        View::composer('components.app-header', function (ViewInstance $view): void {
            $view->with([
                'companyName' => $this->app->make(CompanySettings::class)->name,
                // Stanje kase stoji u zaglavlju, pa ga svaka stranica dobija odavde.
                'fiscalHealth' => $this->app->make(FiscalDeviceHealth::class)->current(),
            ]);
        });

        View::composer('components.user-drawer', function (ViewInstance $view): void {
            $view->with([
                'user' => $this->app->make(UserSettings::class),
                // Bez PIN-a nema šta da se zaključa, pa se radnja i ne nudi.
                'pinEnabled' => $this->app->make(PinLock::class)->isEnabled(),
            ]);
        });

        View::composer(['profile', 'unlock'], function (ViewInstance $view): void {
            $assetBuildHash = $this->app->make(Vite::class)->manifestHash();

            $view->with([
                'pinEnabled' => $this->app->make(PinLock::class)->isEnabled(),
                'appReleaseVersion' => config('nativephp.version'),
                'appBuildCode' => config('nativephp.version_code'),
                'assetBuildHash' => $assetBuildHash === null ? null : strtoupper(substr($assetBuildHash, 0, 8)),
            ]);
        });
    }

    /** NativePHP proces ostaje živ između zahtjeva, pa scoped podešavanja osvježava na resetu. */
    private function keepSettingsFreshBetweenDispatches(): void
    {
        if (! class_exists(Runtime::class)) {
            return;
        }

        Runtime::onReset(fn ($app) => $app->forgetScopedInstances());
    }
}
