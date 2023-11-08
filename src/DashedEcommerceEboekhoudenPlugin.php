<?php

namespace Dashed\DashedEcommerceEboekhouden;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedEcommerceEboekhouden\Filament\Widgets\EboekhoudenOrderStats;
use Dashed\DashedEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage;

class DashedEcommerceEboekhoudenPlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-ecommerce-eboekhouden';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->widgets([
                EboekhoudenOrderStats::class,
            ])
            ->pages([
                EboekhoudenSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}
