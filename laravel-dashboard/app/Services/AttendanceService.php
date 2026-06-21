<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public static function determineClockInStatus(Carbon $clockInAt, ?Schedule $schedule): string
    {
        if (!$schedule || !$schedule->shift) {
            return 'on_time';
        }

        $tolerance = (int) \App\Models\ClinicSetting::get('late_tolerance_minutes', 15);
        $shiftStart = Carbon::parse($schedule->work_date->format('Y-m-d') . ' ' . $schedule->shift->start_time);
        $limit = $shiftStart->copy()->addMinutes($tolerance);

        return $clockInAt->gt($limit) ? 'late' : 'on_time';
    }

    public static function determineClockOutStatus(Carbon $clockOutAt, ?Schedule $schedule): string
    {
        if (!$schedule || !$schedule->shift) {
            return 'on_time';
        }

        $shiftEnd = Carbon::parse($schedule->work_date->format('Y-m-d') . ' ' . $schedule->shift->end_time);
        return $clockOutAt->lt($shiftEnd) ? 'early' : 'on_time';
    }

    public static function getOrCreateToday(User $user, string $workDate): Attendance
    {
        return Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $workDate],
        );
    }
}
