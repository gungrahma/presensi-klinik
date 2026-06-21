<?php

namespace App\Filament\Admin\Resources\Schedules\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.employee_id')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('shift.start_time')
                    ->label('Masuk')
                    ->time('H:i'),
                TextColumn::make('shift.end_time')
                    ->label('Pulang')
                    ->time('H:i'),
                IconColumn::make('is_off')
                    ->label('Libur')
                    ->boolean(),
            ])
            ->defaultSort('work_date', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'employee'))
                    ->searchable()
                    ->preload(),
                Filter::make('work_date')
                    ->form([
                        DatePicker::make('from')->label('Dari')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('to')->label('Sampai')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
                            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date));
                    }),
            ])
            ->headerActions([
                BulkAction::make('generate')
                    ->label('Generate Jadwal Bulanan')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->options(fn () => \App\Models\User::where('role', 'employee')->where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('shift_id')
                            ->label('Shift')
                            ->options(fn () => \App\Models\Shift::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        DatePicker::make('start_date')->label('Dari Tanggal')->required()->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('end_date')->label('Sampai Tanggal')->required()->native(false)->displayFormat('d/m/Y'),
                        Select::make('days')
                            ->label('Hari Kerja')
                            ->multiple()
                            ->options([
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                            ])
                            ->required()
                            ->default([1, 2, 3, 4, 5]),
                    ])
                    ->action(function (array $data) {
                        $start = \Carbon\Carbon::parse($data['start_date']);
                        $end = \Carbon\Carbon::parse($data['end_date']);
                        $userId = $data['user_id'];
                        $shiftId = $data['shift_id'];
                        $days = $data['days'];
                        $count = 0;
                        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                            if (in_array($date->dayOfWeekIso, $days)) {
                                \App\Models\Schedule::updateOrCreate(
                                    ['user_id' => $userId, 'work_date' => $date->toDateString()],
                                    ['shift_id' => $shiftId, 'is_off' => false],
                                );
                                $count++;
                            }
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Jadwal berhasil di-generate')
                            ->body("{$count} jadwal telah dibuat/diperbarui")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
