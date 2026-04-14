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

        cms()->registerSettingsDocs(
            page: \Dashed\DashedEcommerceEboekhouden\Filament\Pages\Settings\EboekhoudenSettingsPage::class,
            title: 'e-Boekhouden instellingen',
            intro: 'Koppel de webshop met e-Boekhouden.nl, zodat facturen en betalingen automatisch in je boekhouding terechtkomen. Per site vul je hier de inloggegevens en de juiste grootboekrekeningen in.',
            sections: [
                [
                    'heading' => 'Wat kun je hier instellen?',
                    'body' => <<<MARKDOWN
Op deze pagina regel je twee dingen:

1. De inloggegevens voor de API koppeling met e-Boekhouden: je gebruikersnaam en twee security codes.
2. De grootboekrekeningen waarop omzet en debiteuren geboekt moeten worden.

Zodra dit klopt, worden facturen automatisch ingeschoten in e-Boekhouden zodra een bestelling daar klaar voor is.
MARKDOWN,
                ],
                [
                    'heading' => 'Hoe zet je dit op?',
                    'body' => <<<MARKDOWN
1. Log in op e-Boekhouden.nl.
2. Ga naar Beheer en open het onderdeel Externe koppelingen of API.
3. Vraag (als je dat nog niet hebt gedaan) de twee beveiligingscodes aan. Je ontvangt dan een Security code 1 en een Security code 2.
4. Vul je gebruikersnaam en de twee security codes in op deze pagina.
5. Vraag bij je boekhouder of in je rekeningschema welke grootboekrekening je gebruikt voor omzet en welke voor debiteuren.
6. Vul deze nummers in bij de bijbehorende velden.
7. Sla de instellingen op en doe een proefboeking om te controleren of alles netjes binnenkomt.
MARKDOWN,
                ],
            ],
            fields: [
                'Gebruikersnaam' => 'Je gebruikersnaam van e-Boekhouden.nl. Dezelfde naam waarmee je inlogt op het portaal.',
                'Security code 1' => 'De eerste beveiligingscode uit het API onderdeel van e-Boekhouden. Deze hoort bij jouw account en is anders dan je gewone wachtwoord.',
                'Security code 2' => 'De tweede beveiligingscode uit het API onderdeel. Samen met code 1 geeft dit de webshop toegang tot de koppeling.',
                'Grootboekrekening omzet' => 'Het rekeningnummer waarop de omzet uit je webshop wordt geboekt. Twijfel je over welk nummer je moet gebruiken? Vraag dit even aan je boekhouder.',
                'Debiteurenrekening' => 'Het rekeningnummer waarop openstaande bedragen van klanten (vorderingen) worden vastgelegd. Ook hiervoor geldt: bij twijfel je boekhouder vragen.',
            ],
            tips: [
                'De security codes zijn niet hetzelfde als je gewone wachtwoord. Vraag ze aan in het API tab van e-Boekhouden, anders blijft de koppeling foutmeldingen geven.',
                'Doe een proefboeking met een kleine bestelling voordat je live gaat. Controleer in e-Boekhouden of de factuur op de juiste grootboekrekening terechtkomt.',
                'Vraag je boekhouder om de juiste rekeningnummers. Een verkeerd ingevulde grootboekrekening levert later veel correctiewerk op.',
                'Zorg dat je security codes veilig bewaart. Iemand met deze codes kan via de API gegevens uit je boekhouding lezen en aanpassen.',
            ],
        );
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->registerSettingsPage(EboekhoudenSettingsPage::class, 'E-boekhouden', 'archive-box', 'Koppel E-boekhouden');

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

        cms()->builder('plugins', [
            new DashedEcommerceEboekhoudenPlugin(),
        ]);
    }
}
