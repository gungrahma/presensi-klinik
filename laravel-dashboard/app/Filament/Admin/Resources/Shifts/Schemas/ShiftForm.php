<?php

namespace App\Filament\Admin\Resources\Shifts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Shift')
                    ->required()
                    ->placeholder('Contoh: Pagi, Siang, Malam')
                    ->maxLength(100),
                TimePicker::make('start_time')
                    ->label('Jam Masuk')
                    ->required()
                    ->seconds(false),
                TimePicker::make('end_time')
                    ->label('Jam Pulang')
                    ->required()
                    ->seconds(false),
                TextInput::make('tolerance_minutes')
                    ->label('Toleransi Telat (menit)')
                    ->required()
                    ->numeric()
                    ->default(15)
                    ->minValue(0)
                    ->maxValue(120)
                    ->suffix('menit'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
