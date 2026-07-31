<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Providers\ShareServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     * This is a security measure to prevent transitive dependencies from
     * automatically registering plugins without your explicit consent.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            // Sistemski dijalog za dijeljenje. Bez njega `Share::file()` zove
            // most koji na uređaju nije registrovan, pa poziv tiho ne uradi ništa —
            // a PDF računa se u WebViewu ne može ni preuzeti.
            ShareServiceProvider::class,
        ];
    }
}
