<?php

namespace App\Filament\Admin\Resources\LeaveRequests\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'approver']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cuti' => 'info',
                        'izin' => 'warning',
                        'sakit' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cuti' => 'Cuti',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                    }),
                TextColumn::make('start_date')
                    ->label('Dari')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('total_days')
                    ->label('Hari')
                    ->suffix(' hari')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    }),
                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->size('sm')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->modalHeading('Setujui Pengajuan')
                    ->modalDescription(fn ($record) => "Setujui pengajuan {$record->type} dari {$record->user->name} ({$record->total_days} hari)?")
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Catatan (opsional)')
                            ->placeholder('Misal: Semoga lekas sembuh')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'admin_note' => $data['admin_note'] ?? null,
                        ]);

                        $record->user->notify(
                            \Filament\Notifications\Notification::make()
                                ->title('Pengajuan Disetujui')
                                ->body("Pengajuan {$record->type} Anda telah disetujui")
                                ->success()
                                ->toDatabase()
                        );

                        Notification::make()
                            ->title('Pengajuan disetujui')
                            ->body("{$record->user->name} · {$record->type} {$record->total_days} hari")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->size('sm')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->modalHeading('Tolak Pengajuan')
                    ->modalDescription(fn ($record) => "Tolak pengajuan {$record->type} dari {$record->user->name}?")
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Misal: Kuota cuti sudah habis')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                            'admin_note' => $data['admin_note'],
                        ]);

                        $record->user->notify(
                            \Filament\Notifications\Notification::make()
                                ->title('Pengajuan Ditolak')
                                ->body("Pengajuan {$record->type} Anda ditolak. Alasan: {$data['admin_note']}")
                                ->danger()
                                ->toDatabase()
                        );

                        Notification::make()
                            ->title('Pengajuan ditolak')
                            ->body("{$record->user->name} · {$record->type}")
                            ->warning()
                            ->send();
                    }),
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat Detail'),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Setujui yang Dipilih')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Pengajuan Terpilih')
                        ->modalSubmitActionLabel('Setujui Semua')
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);
                                    $record->user->notify(
                                        \Filament\Notifications\Notification::make()
                                            ->title('Pengajuan Disetujui')
                                            ->body("Pengajuan {$record->type} Anda telah disetujui")
                                            ->success()
                                            ->toDatabase()
                                    );
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} pengajuan disetujui")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
