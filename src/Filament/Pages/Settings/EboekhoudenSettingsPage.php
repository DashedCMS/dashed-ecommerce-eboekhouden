<?php

namespace Dashed\DashedEcommerceEboekhouden\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Dashed\DashedCore\Models\Customsetting;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedEcommerceEboekhouden\Classes\Eboekhouden;

class EboekhoudenSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'E-boekhouden';

    protected string $view = 'dashed-core::settings.pages.default-settings';
    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["eboekhouden_username_{$site['id']}"] = Customsetting::get('eboekhouden_username', $site['id']);
            $formData["eboekhouden_security_code_1_{$site['id']}"] = Customsetting::get('eboekhouden_security_code_1', $site['id']);
            $formData["eboekhouden_security_code_2_{$site['id']}"] = Customsetting::get('eboekhouden_security_code_2', $site['id']);
            $formData["eboekhouden_grootboek_rekening_{$site['id']}"] = Customsetting::get('eboekhouden_grootboek_rekening', $site['id']);
            $formData["eboekhouden_debiteuren_rekening_{$site['id']}"] = Customsetting::get('eboekhouden_debiteuren_rekening', $site['id']);
            $formData["eboekhouden_connected_{$site['id']}"] = Customsetting::get('eboekhouden_connected', $site['id'], 0) ? true : false;
        }

        $this->form->fill($formData);
    }

    public function form(Schema $schema): Schema
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $newSchema = [
                TextEntry::make('label')
                    ->state("E-boekhouden voor {$site['name']}")
                    ->state('Activeer E-boekhouden.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextEntry::make("E-boekhouden is " . (! Customsetting::get('eboekhouden_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->state(Customsetting::get('eboekhouden_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("eboekhouden_username_{$site['id']}")
                    ->label('E-boekhouden username')
                    ->maxLength(255),
                TextInput::make("eboekhouden_security_code_1_{$site['id']}")
                    ->label('E-boekhouden security code 1')
                    ->maxLength(255),
                TextInput::make("eboekhouden_security_code_2_{$site['id']}")
                    ->label('E-boekhouden security code 2')
                    ->maxLength(255),
                TextInput::make("eboekhouden_grootboek_rekening_{$site['id']}")
                    ->label('E-boekhouden grootboekrekening')
                    ->maxLength(255),
                TextInput::make("eboekhouden_debiteuren_rekening_{$site['id']}")
                    ->label('E-boekhouden debiteurenrekening')
                    ->maxLength(255),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($newSchema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $schema->schema($tabGroups)
            ->statePath('data');
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('eboekhouden_username', $this->form->getState()["eboekhouden_username_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_security_code_1', $this->form->getState()["eboekhouden_security_code_1_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_security_code_2', $this->form->getState()["eboekhouden_security_code_2_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_grootboek_rekening', $this->form->getState()["eboekhouden_grootboek_rekening_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_debiteuren_rekening', $this->form->getState()["eboekhouden_debiteuren_rekening_{$site['id']}"], $site['id']);
            Customsetting::set('eboekhouden_connected', Eboekhouden::isConnected($site['id']), $site['id']);
        }

        Notification::make()
            ->title('De E-boekhouden instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(EboekhoudenSettingsPage::getUrl());
    }
}
