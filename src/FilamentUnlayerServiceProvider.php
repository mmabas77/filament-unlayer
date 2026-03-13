<?php

declare(strict_types=1);

namespace Mmabas77\FilamentUnlayer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentUnlayerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('mmabas77-filament-unlayer')
            ->hasConfigFile('filament-unlayer')
            ->hasViews('filament-unlayer');
    }
}
