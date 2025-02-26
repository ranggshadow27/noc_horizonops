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
            // ->label(__('filament-panels::pages/auth/register.form.name.label'))
            ->label("Your Name")
            ->required()
            ->live(true) // Memungkinkan auto-update di field lain
            ->afterStateUpdated(
                function ($state, callable $set) {
                    $set('email', Str::slug($state, '.') . '@mahaga-pratama.co.id');
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
}
