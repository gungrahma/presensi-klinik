<?php

namespace App\Filament\Admin\Resources\Attendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'employee'))
                    ->required()
                    ->searchable()
                    ->preload(),
                DatePicker::make('work_date')
                    ->label('Tanggal')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DateTimePicker::make('clock_in_at')
                    ->label('Jam Masuk')
                    ->seconds(false),
                Select::make('clock_in_status')
                    ->label('Status Masuk')
                    ->options(['on_time' => 'Tepat', 'late' => 'Telat']),
                TextInput::make('clock_in_distance_m')
                    ->label('Jarak Masuk (m)')
                    ->numeric(),
                FileUpload::make('clock_in_photo_path')
                    ->label('Foto Masuk')
                    ->image()
                    ->directory('attendances')
                    ->maxSize(5120),
                DateTimePicker::make('clock_out_at')
                    ->label('Jam Pulang')
                    ->seconds(false),
                Select::make('clock_out_status')
                    ->label('Status Pulang')
                    ->options(['on_time' => 'Tepat', 'early' => 'Pulang Cepat']),
                TextInput::make('clock_out_distance_m')
                    ->label('Jarak Pulang (m)')
                    ->numeric(),
                FileUpload::make('clock_out_photo_path')
                    ->label('Foto Pulang')
                    ->image()
                    ->directory('attendances')
                    ->maxSize(5120),
            ]);
    }
}
