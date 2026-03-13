<?php

declare(strict_types=1);

namespace Mmabas77\FilamentUnlayer;

use Illuminate\Filesystem\Filesystem;
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

    public function packageBooted(): void
    {
        // Auto-publish dist assets to public/vendor/filament-unlayer on boot.
        // This ensures assets are always available without a manual artisan publish step.
        $dest = public_path('vendor/filament-unlayer');
        $src  = __DIR__ . '/../dist';

        if (! is_dir($dest) || $this->assetsAreStale($src, $dest)) {
            app(Filesystem::class)->copyDirectory($src, $dest);
        }

        $this->publishes([
            $src => $dest,
        ], 'filament-unlayer-assets');
    }

    /**
     * Returns true if the source dist files are newer than the deployed copies.
     */
    private function assetsAreStale(string $src, string $dest): bool
    {
        $marker = $dest . '/grapes.min.js';

        if (! file_exists($marker)) {
            return true;
        }

        return filemtime($src . '/grapes.min.js') > filemtime($marker);
    }
}
