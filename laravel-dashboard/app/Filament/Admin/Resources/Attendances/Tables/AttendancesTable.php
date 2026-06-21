<?php

namespace App\Filament\Admin\Resources\Attendances\Tables;

use App\Exports\AttendancesExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Excel as ExcelType;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'user.schedules']))
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
                TextColumn::make('clock_in_at')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),
                TextColumn::make('clock_in_status')
                    ->label('Status Masuk')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'on_time' => 'success',
                        'late' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'on_time' => 'Tepat',
                        'late' => 'Telat',
                        default => '-',
                    }),
                TextColumn::make('clock_in_distance_m')
                    ->label('Jarak (m)')
                    ->suffix(' m')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clock_out_at')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->placeholder('-'),
                TextColumn::make('clock_out_status')
                    ->label('Status Pulang')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'on_time' => 'success',
                        'early' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'on_time' => 'Tepat',
                        'early' => 'Pulang Cepat',
                        default => '-',
                    }),
                ImageColumn::make('clock_in_photo_path')
                    ->label('Foto Masuk')
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('work_date', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name', fn ($query) => $query->where('role', 'employee'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('clock_in_status')
                    ->label('Status Masuk')
                    ->options([
                        'on_time' => 'Tepat',
                        'late' => 'Telat',
                    ]),
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
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->form([
                        DatePicker::make('from')->label('Dari')->required()->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('to')->label('Sampai')->required()->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->action(function (array $data) {
                        $filename = 'absensi-' . $data['from'] . '_' . $data['to'] . '.xlsx';
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new AttendancesExport($data['from'], $data['to']),
                            $filename,
                            ExcelType::XLSX,
                        );
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
