<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden;

use Qubiqx\QcommerceEcommerceEboekhouden\Commands\QcommerceEcommerceEboekhoudenCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QcommerceEcommerceEboekhoudenServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('qcommerce-ecommerce-eboekhouden')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_qcommerce-ecommerce-eboekhouden_table')
            ->hasCommand(QcommerceEcommerceEboekhoudenCommand::class);
    }
}
