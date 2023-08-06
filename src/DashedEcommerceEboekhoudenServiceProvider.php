<?php

namespace Dashed\DashedEcommerceEboekhouden;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceEboekhouden\Commands\PushOrdersToEboekhoudenCommand;
use Dashed\DashedEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage;
use Dashed\DashedEcommerceEboekhouden\Filament\Widgets\EboekhoudenOrderStats;
use Dashed\DashedEcommerceEboekhouden\Livewire\Orders\ShowEboekhoudenShopOrder;
use Dashed\DashedEcommerceEboekhouden\Models\EboekhoudenOrder;
use Spatie\LaravelPackageTools\Package;

class DashedEcommerceEboekhoudenServiceProvider extends PluginServiceProvider
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
                    'icon' => 'archive',
                    'page' => EboekhoudenSettingsPage::class,
                ],
            ])
        );

        ecommerce()->widgets(
            'orders',
            array_merge(ecommerce()->widgets('orders'), [
                'show-eboekhouden-order' => [
                    'name' => 'show-eboekhouden-order',
                    'width' => 'sidebar',
                ],
            ])
        );

        $package
            ->name('dashed-ecommerce-eboekhouden')
            ->hasViews()
            ->hasCommands([
                PushOrdersToEboekhoudenCommand::class,
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
            EboekhoudenOrderStats::class,
        ]);
    }
}
