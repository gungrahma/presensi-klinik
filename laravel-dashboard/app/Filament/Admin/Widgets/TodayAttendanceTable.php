<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TodayAttendanceTable extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public static function canLazyLoad(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $today = Carbon::today()->toDateString();

        return $table
            ->heading('Absensi Hari Ini — ' . Carbon::today()->translatedFormat('l, d F Y'))
            ->query(
                fn () => Attendance::query()->with('user')->where('work_date', $today)->orderBy('clock_in_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('user.employee_id')
                    ->label('NIK')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clock_in_at')
                    ->label('Masuk')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('clock_in_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'on_time' => 'success',
                        'late' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'on_time' => 'Tepat',
                        'late' => 'Telat',
                        default => 'Belum',
                    }),
                TextColumn::make('clock_out_at')
                    ->label('Pulang')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('clock_out_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'on_time' => 'success',
                        'early' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'on_time' => 'Tepat',
                        'early' => 'Pulang Cepat',
                        default => '—',
                    }),
            ])
            ->paginated(false);
    }
}
