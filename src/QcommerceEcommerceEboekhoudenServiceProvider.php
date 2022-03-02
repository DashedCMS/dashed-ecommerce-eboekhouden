<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Qubiqx\QcommerceEcommerceEboekhouden\Commands\PushOrdersToEboekhoudenCommand;
use Qubiqx\QcommerceEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage;
use Qubiqx\QcommerceEcommerceEboekhouden\Filament\Widgets\EboekhoudenOrderStats;
use Qubiqx\QcommerceEcommerceEboekhouden\Livewire\Orders\ShowEboekhoudenShopOrder;
use Qubiqx\QcommerceEcommerceEboekhouden\Models\EboekhoudenOrder;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommerceEboekhoudenServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-eboekhouden';

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
            ->name('qcommerce-ecommerce-eboekhouden')
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
