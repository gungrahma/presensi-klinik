<?php

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class PendingLeaveRequests extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public static function canLazyLoad(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pengajuan Menunggu Persetujuan')
            ->query(
                fn () => LeaveRequest::query()->with('user')->where('status', 'pending')->latest()
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
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
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('period')
                    ->label('Periode')
                    ->getStateUsing(fn ($record) => $record->start_date->format('d M') . ' – ' . $record->end_date->format('d M Y'))
                    ->description(fn ($record) => "{$record->total_days} hari"),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->size('sm')
                    ->modalHeading('Setujui Pengajuan')
                    ->modalDescription(fn ($record) => "Setujui {$record->type} dari {$record->user->name}?")
                    ->modalSubmitActionLabel('Setujui')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Catatan (opsional)')
                            ->rows(2),
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
                            ->title('Disetujui')
                            ->body("{$record->user->name}")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->size('sm')
                    ->modalHeading('Tolak Pengajuan')
                    ->modalDescription(fn ($record) => "Tolak {$record->type} dari {$record->user->name}?")
                    ->modalSubmitActionLabel('Tolak')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Alasan')
                            ->required()
                            ->rows(2),
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
                            ->title('Ditolak')
                            ->body("{$record->user->name}")
                            ->warning()
                            ->send();
                    }),
            ])
            ->paginated([5, 10, 25]);
    }
}
