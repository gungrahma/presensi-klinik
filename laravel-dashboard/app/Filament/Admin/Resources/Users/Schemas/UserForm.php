<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('employee_id')
                    ->label('NIK Karyawan')
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('position')
                    ->label('Jabatan')
                    ->maxLength(100)
                    ->placeholder('Contoh: Perawat, Dokter, Resepsionis'),
                TextInput::make('phone')
                    ->label('No. HP')
                    ->tel()
                    ->maxLength(20),
                FileUpload::make('photo')
                    ->label('Foto Profil')
                    ->image()
                    ->directory('employees')
                    ->imageEditor()
                    ->maxSize(2048),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(6)
                    ->maxLength(255)
                    ->helperText('Minimal 6 karakter. Kosongkan jika tidak ingin mengubah password (mode edit).'),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'employee' => 'Karyawan',
                    ])
                    ->default('employee')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
