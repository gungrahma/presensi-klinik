<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TodayDataSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();
        $employees = User::where('role', 'employee')->where('is_active', true)->get();
        $shifts = Shift::where('is_active', true)->get();

        if ($employees->isEmpty() || $shifts->isEmpty()) {
            return;
        }

        foreach ($employees as $idx => $employee) {
            $shift = $shifts[$idx % $shifts->count()];

            Schedule::updateOrCreate(
                ['user_id' => $employee->id, 'work_date' => $today],
                ['shift_id' => $shift->id, 'is_off' => false],
            );
        }

        $budi = $employees->firstWhere('email', 'budi@absensiklinik.test');
        if ($budi) {
            $budiShift = $shifts->firstWhere('name', 'Shift Pagi');
            Attendance::updateOrCreate(
                ['user_id' => $budi->id, 'work_date' => $today],
                [
                    'clock_in_at' => Carbon::parse($today . ' 07:05:00'),
                    'clock_in_lat' => -6.2,
                    'clock_in_lng' => 106.816666,
                    'clock_in_distance_m' => 15,
                    'clock_in_status' => 'on_time',
                ],
            );
        }

        $siti = $employees->firstWhere('email', 'siti@absensiklinik.test');
        if ($siti) {
            $sitiShift = $shifts->firstWhere('name', 'Shift Siang');
            Attendance::updateOrCreate(
                ['user_id' => $siti->id, 'work_date' => $today],
                [
                    'clock_in_at' => Carbon::parse($today . ' 12:25:00'),
                    'clock_in_lat' => -6.2,
                    'clock_in_lng' => 106.816666,
                    'clock_in_distance_m' => 22,
                    'clock_in_status' => 'late',
                ],
            );
        }
    }
}
