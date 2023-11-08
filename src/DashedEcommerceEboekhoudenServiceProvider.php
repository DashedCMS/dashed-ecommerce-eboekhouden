<?php

namespace Dashed\DashedEcommerceEboekhouden;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommerceCore\Models\Order;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommerceEboekhouden\Models\EboekhoudenOrder;
use Dashed\DashedEcommerceEboekhouden\Commands\PushOrdersToEboekhoudenCommand;
use Dashed\DashedEcommerceEboekhouden\Livewire\Orders\ShowEboekhoudenShopOrder;
use Dashed\DashedEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage;

class DashedEcommerceEboekhoudenServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-ecommerce-eboekhouden';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(PushOrdersToEboekhoudenCommand::class)->everyFifteenMinutes();
        });

        Livewire::component('show-eboekhouden-order', ShowEboekhoudenShopOrder::class);

        Order::addDynamicRelation('eboekhoudenOrder', function (Order $model) {
            return $model->hasOne(EboekhoudenOrder::class);
        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'eboekhouden' => [
                    'name' => 'E-boekhouden',
                    'description' => 'Koppel E-boekhouden',
                    'icon' => 'archive-box',
                    'page' => EboekhoudenSettingsPage::class,
                ],
            ])
        );

        //        ecommerce()->widgets(
        //            'orders',
        //            array_merge(ecommerce()->widgets('orders'), [
        //                'show-eboekhouden-order' => [
        //                    'name' => 'show-eboekhouden-order',
        //                    'width' => 'sidebar',
        //                ],
        //            ])
        //        );

        $package
            ->name('dashed-ecommerce-eboekhouden')
            ->hasViews()
            ->hasCommands([
                PushOrdersToEboekhoudenCommand::class,
            ]);
    }
}
