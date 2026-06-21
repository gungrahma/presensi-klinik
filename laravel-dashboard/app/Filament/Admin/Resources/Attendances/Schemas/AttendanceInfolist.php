<?php

namespace App\Filament\Admin\Resources\Attendances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('work_date')
                    ->date(),
                TextEntry::make('clock_in_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('clock_in_photo_path')
                    ->placeholder('-'),
                TextEntry::make('clock_in_lat')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_in_lng')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_in_distance_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_in_accuracy_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_in_status')
                    ->placeholder('-'),
                TextEntry::make('clock_out_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('clock_out_photo_path')
                    ->placeholder('-'),
                TextEntry::make('clock_out_lat')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_out_lng')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_out_distance_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_out_accuracy_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('clock_out_status')
                    ->placeholder('-'),
                TextEntry::make('ip_address')
                    ->placeholder('-'),
                TextEntry::make('user_agent')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
