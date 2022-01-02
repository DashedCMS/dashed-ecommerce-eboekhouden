<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceEboekhoudenServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-eboekhouden';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
//            $schedule->command(PushProductsToeboekhouden::class)->everyFiveMinutes();
//            $schedule->command(SyncProductStockWitheboekhouden::class)->everyFiveMinutes();
//            $schedule->command(PushOrdersToeboekhoudenCommand::class)->everyFiveMinutes();
//            $schedule->command(UpdateOrdersToeboekhoudenCommand::class)->everyFifteenMinutes();
        });

//        Livewire::component('show-eboekhouden-order', ShoweboekhoudenOrder::class);
//        Livewire::component('edit-eboekhouden-product', EditeboekhoudenProduct::class);

//        Order::addDynamicRelation('eboekhoudenOrder', function (Order $model) {
//            return $model->hasOne(eboekhoudenOrder::class);
//        });
//        Product::addDynamicRelation('eboekhoudenProduct', function (Product $model) {
//            return $model->hasOne(eboekhoudenProduct::class);
//        });
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
                    'icon' => 'archive',
                    'page' => EboekhoudenSettingsPage::class,
                ],
            ])
        );

        $package
            ->name('qcommerce-ecommerce-eboekhouden')
            ->hasViews()
            ->hasCommands([
//                PushProductsToeboekhouden::class,
//                SyncProductStockWitheboekhouden::class,
//                PushOrdersToeboekhoudenCommand::class,
//                UpdateOrdersToeboekhoudenCommand::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            EboekhoudenSettingsPage::class,
        ]);
    }

    protected function getWidgets(): array
    {
        return array_merge(parent::getWidgets(), [
//            eboekhoudenOrderStats::class,
        ]);
    }
}
