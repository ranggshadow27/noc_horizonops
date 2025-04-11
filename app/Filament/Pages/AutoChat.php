<?php

namespace App\Filament\Pages;

use App\Models\ChatTemplate;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use App\Models\SiteDetail;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Webbingbrasil\FilamentCopyActions\Pages\Actions\CopyAction;

class AutoChat extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.auto-chat';

    public $gender = '';
    public $siteId = '';
    public $templateId = '';
    public $generatedChat = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getActions(): array
    {
        return [
            CopyAction::make()
                ->disabled(fn() => empty($this->generatedChat))
                ->copyable(fn() => $this->generatedChat),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('siteId')
                ->label('Site ID')
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return SiteDetail::where('site_id', 'like', "%{$search}%")
                        ->orWhere('site_name', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($site) {
                            return [$site->site_id => "{$site->site_id} - {$site->site_name}"];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $site = SiteDetail::where('site_id', $value)->first();
                    return $site ? "{$site->site_id} - {$site->site_name}" : null;
                })
                ->required()
                ->placeholder('Pilih Site ID'),

            Select::make('templateId')
                ->label('Chat Template')
                ->options(function () {
                    return ChatTemplate::pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->required()
                ->placeholder('Pilih Template')
                ->default(function () {
                    return ChatTemplate::first()?->id;
                }),

            Select::make('gender')
                ->label('Gender')
                ->default('male')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                ])
                ->required()
                ->placeholder('Pilih Gender'),

            Textarea::make('generatedChat')
                ->label('Generated Chat')
                ->autosize()
                ->disabled(),

            Actions::make([
                Actions\Action::make('generate')
                    ->label('Generate Chat')
                    ->action('generateChat'),
            ]),
        ];
    }

    public function generateChat(): void
    {
        $site = SiteDetail::where('site_id', $this->siteId)->first();
        $template = ChatTemplate::find($this->templateId);

        if (!$site) {
            $this->generatedChat = '';
            Notification::make()
                ->title('Error')
                ->body('Site ID tidak ditemukan!')
                ->danger()
                ->send();
        } elseif (!$template) {
            $this->generatedChat = '';
            Notification::make()
                ->title('Error')
                ->body('Template tidak ditemukan!')
                ->danger()
                ->send();
        } else {
            $timeOfDay = now()->hour < 12 ? 'Pagi' : (now()->hour < 17 ? 'Siang' : 'Malam');
            $genderText = $this->gender === 'male' ? 'Bapak' : 'Ibu';

            $placeholders = [
                '{site_id}' => $site->site_id,
                '{nama_site}' => $site->site_name,
                '{provinsi}' => $site->province,
                '{time}' => $timeOfDay,
                '{gender}' => $genderText,
            ];

            $this->generatedChat = str_replace(
                array_keys($placeholders),
                array_values($placeholders),
                $template->template
            );
        }

        $this->form->fill([
            'siteId' => $this->siteId,
            'templateId' => $this->templateId,
            'generatedChat' => $this->generatedChat,
        ]);
    }

    // public function generateChat(): void
    // {
    //     $site = SiteDetail::where('site_id', $this->siteId)->first();

    //     if ($site) {
    //         $this->generatedChat = "Selamat Malam,\n\nkami penyedia wifi dilokasi {$site->site_id} - {$site->site_name} - {$site->province}, apakah saat ini ada kendala dengan internetnya?\n\nTerimakasih";
    //     } else {
    //         $this->generatedChat = 'Site ID tidak ditemukan!';
    //     }

    //     $this->form->fill([
    //         'siteId' => $this->siteId,
    //         'generatedChat' => $this->generatedChat,
    //     ]);
    // }
}
