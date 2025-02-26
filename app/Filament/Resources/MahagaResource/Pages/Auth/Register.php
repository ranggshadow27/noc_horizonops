<?php

namespace App\Filament\Resources\MahagaResource\Pages\Auth;

use App\Filament\Resources\MahagaResource;
use Filament\Resources\Pages\Page;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Illuminate\Support\Str;


class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getUsernameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('number')
            ->label('Phone Number')
            ->onlyCountries(['id'])
            ->required();
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label("Your Name")
            ->required()
            ->live(true) // Memungkinkan auto-update di field lain
            ->afterStateUpdated(
                function ($state, callable $set) {
                    // Ambil hanya dua kata pertama dari input
                    $words = explode(' ', trim($state));
                    $firstTwoWords = array_slice($words, 0, 2);

                    // Gabungkan dengan titik, buat slug, dan tambahkan domain email
                    $formatName = Str::slug(implode(' ', $firstTwoWords), '.');

                    // Set nilai email dan format nama jadi kapital di awal kata
                    $set('email', $formatName . '@mahaga-pratama.co.id');
                    $set('username', $formatName);

                    $set('name', ucwords(strtolower($state)));
                }
            )
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            // ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->label("Email Address")
            // ->suffix('@mahaga-pratama.co.id')
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            // ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->label("Username")
            // ->suffix('@mahaga-pratama.co.id')
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }
}
