<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ServiceProvider;
use App\Models\SpPerformance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction; // Tambahkan EditAction
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;

class ManageServiceProviders extends Page implements HasTable, HasActions
{
    use HasPageShield;

    use InteractsWithTable;

    protected static ?string $navigationLabel = 'SP Performance';
    protected ?string $subheading = 'Service Provider Performance Percentage';
    protected static ?string $title = 'SP Performance';
    protected static ?string $navigationGroup = 'Trouble Tickets';
    protected static ?string $navigationIcon = 'phosphor-gauge-duotone';

    protected static string $view = 'filament.pages.manage-service-providers';

    public function table(Table $table): Table
    {
        $sps = ServiceProvider::all(); // Ambil semua SP buat default repeater
        return $table
            ->recordTitleAttribute('sp_name')
            ->query(ServiceProvider::query())
            ->columns([
                TextColumn::make('sp_id')
                    ->label('ID'),
                TextColumn::make('sp_name')
                    ->searchable()
                    ->label('SP Name'),
                TextColumn::make('total_site')
                    ->label('Total Site')
                    ->badge(),
            ])
            ->filters([])
            ->headerActions([
                ActionGroup::make([
                    Action::make('add_sp')
                        ->icon('phosphor-user-circle-plus-duotone')
                        ->label('Add Service Provider')
                        ->form([
                            TextInput::make('sp_name')
                                ->required(),
                            TextInput::make('total_site')
                                ->required()
                                ->integer(),
                        ])
                        ->action(function (array $data) {
                            try {
                                ServiceProvider::create($data); // sp_id otomatis dari model
                                Notification::make()->success()->title('SP Created')->send();
                            } catch (\Exception $e) {
                                Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                            }
                        })
                        ->modalHeading('Add New SP')
                        ->modalSubmitActionLabel('Save')
                        ->visible(true),

                    Action::make('add_performance')
                        ->icon('phosphor-plus-duotone')
                        ->label('Add SP Performance')
                        ->form([
                            DatePicker::make('performance_date')
                                ->label('Date')
                                ->required()
                                ->native(false)
                                ->default(now()),

                            Repeater::make('performances')
                                ->schema([
                                    Select::make('sp_id')
                                        ->label('SP Name')
                                        ->native(false)
                                        ->searchable()
                                        ->options($sps->pluck('sp_name', 'sp_id'))
                                        ->required(),
                                    TextInput::make('today_ticket')
                                        ->label('Today Ticket')
                                        ->required()->integer(),
                                    TextInput::make('today_rank')
                                        ->label('Today Rank')
                                        ->required()->integer(),
                                ])
                                // ->default(function () use ($sps) {
                                //     return $sps->map(fn($sp) => [
                                //         'sp_id' => $sp->sp_id,
                                //         'today_ticket' => 0, // Default 0 atau kosong
                                //     ])->toArray();
                                // })
                                ->columns(3)
                                ->collapsible(),
                        ])
                        ->action(function (array $data) {
                            $date = $data['performance_date'];
                            $formattedDate = Carbon::parse($date)->format('dmy');

                            foreach ($data['performances'] as $perf) {
                                $sp = ServiceProvider::find($perf['sp_id']);
                                $prefix = Str::upper($sp->sp_name);
                                $sp_perf_id = $prefix . '-' . $formattedDate;

                                SpPerformance::create([
                                    'sp_perf_id' => $sp_perf_id,
                                    'sp_id' => $perf['sp_id'],
                                    'today_ticket' => $perf['today_ticket'],
                                    'today_rank' => $perf['today_rank'],
                                    'created_at' => $date,
                                ]);
                            }
                        })
                        ->modalHeading('Add Performances')
                        ->modalSubmitActionLabel('Submit'),
                ])
                    ->label('Create Data')
                    ->icon('phosphor-circles-four-duotone')
                    ->size(ActionSize::Medium)
                    ->color('gray')
                    ->button()
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit SP')
                        ->icon('phosphor-pencil-duotone')
                        ->form([
                            TextInput::make('sp_name')
                                ->label('SP Name')
                                ->required(),
                            TextInput::make('total_site')
                                ->label('Total Site')
                                ->required()
                                ->integer(),
                        ]),
                ])
                    ->icon('phosphor-dots-three-vertical-duotone') // Menggunakan icon dots-three-vertical phosphor duotone
                    ->color('gray')
                    ->tooltip('Actions')
            ])
            ->paginated([2, 5, 10])
            ->defaultPaginationPageOption(5)
            ->bulkActions([]);
    }
}
