<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Providers\ShareServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    /** @return array<int, class-string<ServiceProvider>> */
    public function plugins(): array
    {
        return [
            ShareServiceProvider::class,
        ];
    }
}
