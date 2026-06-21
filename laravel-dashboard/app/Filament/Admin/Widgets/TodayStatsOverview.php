<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canLazyLoad(): bool
    {
        return true;
    }

    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();
        $totalEmployees = User::where('role', 'employee')->where('is_active', true)->count();

        $todayAttendances = Attendance::where('work_date', $today)->get();
        $hadir = $todayAttendances->whereNotNull('clock_in_at')->count();
        $telat = $todayAttendances->where('clock_in_status', 'late')->count();
        $belumClockIn = $totalEmployees - $hadir;
        $kehadiranRate = $totalEmployees > 0 ? round(($hadir / $totalEmployees) * 100) : 0;

        return [
            Stat::make('Total Karyawan', $totalEmployees)
                ->description('Karyawan aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Hadir Hari Ini', $hadir)
                ->description("Tingkat kehadiran: {$kehadiranRate}%")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Terlambat', $telat)
                ->description('Clock in melampaui toleransi')
                ->descriptionIcon('heroicon-m-clock')
                ->color($telat > 0 ? 'danger' : 'gray'),
            Stat::make('Belum Clock In', $belumClockIn)
                ->description($belumClockIn > 0 ? 'Belum absen masuk' : 'Semua sudah hadir')
                ->descriptionIcon('heroicon-m-user-minus')
                ->color($belumClockIn > 0 ? 'warning' : 'success'),
        ];
    }
}
