<?php

namespace Asmit\FilamentMention;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMentionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('asmit-filament-mention')
            ->hasViews()
            ->hasConfigFile(['filament-mention'])
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $installCommand) {
                $installCommand->publishConfigFile()
                    ->publishAssets();
            });
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make(id: 'asmit-filament-mention', path: __DIR__ . '/../resources/dist/js/tributejs.js'),
            Css::make(id: 'asmit-filament-mention', path: __DIR__ . '/../resources/css/asmit-filament-mention.css')
                ->loadedOnRequest(),
        ], package: 'asmit/filament-mention');
    }
}
