<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('role', 'employee')->where('is_active', true)->get();
        $shifts = Shift::where('is_active', true)->get();

        if ($employees->isEmpty() || $shifts->isEmpty()) {
            return;
        }

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();

        foreach ($employees as $idx => $employee) {
            for ($d = 0; $d < $today->day; $d++) {
                $date = $startOfMonth->copy()->addDays($d);
                if ($date->isSunday()) {
                    continue;
                }

                $shift = $shifts[$idx % $shifts->count()];
                $isLate = (string) Str::random(1) === 'a' && mt_rand(0, 100) < 30;

                Schedule::firstOrCreate(
                    ['user_id' => $employee->id, 'work_date' => $date->toDateString()],
                    ['shift_id' => $shift->id, 'is_off' => false],
                );

                $baseClockIn = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
                $baseClockOut = Carbon::parse($date->toDateString() . ' ' . $shift->end_time);

                $clockIn = $baseClockIn->copy();
                if ($isLate) {
                    $clockIn->addMinutes($shift->tolerance_minutes + mt_rand(5, 60));
                } else {
                    $clockIn->subMinutes(mt_rand(0, 10));
                }

                $clockOut = $baseClockOut->copy();
                if (mt_rand(0, 100) < 20) {
                    $clockOut->subMinutes(mt_rand(10, 30));
                } else {
                    $clockOut->addMinutes(mt_rand(0, 15));
                }

                Attendance::updateOrCreate(
                    ['user_id' => $employee->id, 'work_date' => $date->toDateString()],
                    [
                        'clock_in_at' => $clockIn,
                        'clock_out_at' => $clockOut,
                        'clock_in_status' => $isLate ? 'late' : 'on_time',
                        'clock_out_status' => $clockOut->lt($baseClockOut) ? 'early' : 'on_time',
                        'clock_in_lat' => -6.2,
                        'clock_in_lng' => 106.816666,
                        'clock_in_distance_m' => mt_rand(5, 80),
                        'clock_out_lat' => -6.2,
                        'clock_out_lng' => 106.816666,
                        'clock_out_distance_m' => mt_rand(5, 80),
                    ],
                );
            }
        }

        $pendingEmployee = $employees->first();
        if ($pendingEmployee) {
            LeaveRequest::create([
                'user_id' => $pendingEmployee->id,
                'type' => 'cuti',
                'start_date' => $today->copy()->addDays(7)->toDateString(),
                'end_date' => $today->copy()->addDays(9)->toDateString(),
                'total_days' => 3,
                'reason' => 'Acara keluarga (nikah saudara) di luar kota. Mohon disetujui.',
                'status' => 'pending',
            ]);

            LeaveRequest::create([
                'user_id' => $employees->skip(1)->first()->id,
                'type' => 'sakit',
                'start_date' => $today->copy()->addDays(1)->toDateString(),
                'end_date' => $today->copy()->addDays(2)->toDateString(),
                'total_days' => 2,
                'reason' => 'Demam dan flu, ada surat dokter terlampir.',
                'status' => 'pending',
            ]);

            LeaveRequest::create([
                'user_id' => $employees->skip(2)->first()->id,
                'type' => 'izin',
                'start_date' => $today->copy()->subDays(2)->toDateString(),
                'end_date' => $today->copy()->subDays(2)->toDateString(),
                'total_days' => 1,
                'reason' => 'Mengurus administrasi KTP di kelurahan.',
                'status' => 'approved',
                'approved_by' => 1,
                'approved_at' => now()->subDays(3),
                'admin_note' => 'Disetujui, harap lengkapi dokumen.',
            ]);
        }
    }
}
