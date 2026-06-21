<?php

namespace App\Filament\Admin\Resources\Schedules\Schemas;

use App\Models\Shift;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'employee')->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                DatePicker::make('work_date')
                    ->label('Tanggal')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now()),
                Toggle::make('is_off')
                    ->label('Hari Libur')
                    ->default(false)
                    ->live()
                    ->helperText('Aktifkan jika karyawan tidak masuk di tanggal ini'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
