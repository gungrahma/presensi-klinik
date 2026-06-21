<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Attendance;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivities extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public static function canLazyLoad(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aktivitas Terakhir')
            ->query(
                fn () => Attendance::query()->with('user')->latest('updated_at')->limit(10)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->weight('bold'),
                TextColumn::make('work_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                TextColumn::make('clock_in_at')
                    ->label('Masuk')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('clock_out_at')
                    ->label('Pulang')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since(),
            ])
            ->paginated(false);
    }
}
