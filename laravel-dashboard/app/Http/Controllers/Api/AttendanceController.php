<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Services\AttendanceService;
use App\Services\GeofenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        return response()->json([
            'attendance' => $attendance ? $this->transform($attendance) : null,
            'settings' => [
                'clinic_lat' => (float) \App\Models\ClinicSetting::get('clinic_lat'),
                'clinic_lng' => (float) \App\Models\ClinicSetting::get('clinic_lng'),
                'radius_meter' => (int) \App\Models\ClinicSetting::get('radius_meter', 100),
                'late_tolerance' => (int) \App\Models\ClinicSetting::get('late_tolerance_minutes', 15),
                'min_clock_out_minutes' => (int) \App\Models\ClinicSetting::get('min_clock_out_minutes', 240),
            ],
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $existing = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if ($existing && $existing->clock_in_at) {
            return response()->json([
                'message' => 'Anda sudah clock in hari ini',
            ], 422);
        }

        $geofence = GeofenceService::isWithinRadius(
            (float) $request->lat,
            (float) $request->lng,
        );

        if (!$geofence['within']) {
            return response()->json([
                'message' => "Anda berada di luar radius klinik ({$geofence['distance_m']}m dari pusat, maksimal {$geofence['radius_m']}m)",
                'geofence' => $geofence,
            ], 422);
        }

        $schedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $now = Carbon::now();
        $status = AttendanceService::determineClockInStatus($now, $schedule);

        $photoPath = $request->file('photo')->store("attendances/{$user->id}/" . $today, 'public');

        $attendance = DB::transaction(function () use ($user, $today, $now, $request, $photoPath, $status, $geofence) {
            return Attendance::updateOrCreate(
                ['user_id' => $user->id, 'work_date' => $today],
                [
                    'clock_in_at' => $now,
                    'clock_in_photo_path' => $photoPath,
                    'clock_in_lat' => $request->lat,
                    'clock_in_lng' => $request->lng,
                    'clock_in_distance_m' => $geofence['distance_m'],
                    'clock_in_accuracy_m' => $request->accuracy ? (int) $request->accuracy : null,
                    'clock_in_status' => $status,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 250),
                ],
            );
        });

        return response()->json([
            'message' => 'Clock in berhasil',
            'attendance' => $this->transform($attendance),
        ]);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in_at) {
            return response()->json([
                'message' => 'Anda belum clock in hari ini',
            ], 422);
        }

        if ($attendance->clock_out_at) {
            return response()->json([
                'message' => 'Anda sudah clock out hari ini',
            ], 422);
        }

        $minMinutes = (int) \App\Models\ClinicSetting::get('min_clock_out_minutes', 240);
        $workedMinutes = Carbon::now()->diffInMinutes($attendance->clock_in_at);
        if ($workedMinutes < $minMinutes) {
            return response()->json([
                'message' => "Durasi kerja belum cukup ({$workedMinutes} menit, minimal {$minMinutes} menit)",
            ], 422);
        }

        $geofence = GeofenceService::isWithinRadius(
            (float) $request->lat,
            (float) $request->lng,
        );

        if (!$geofence['within']) {
            return response()->json([
                'message' => "Anda berada di luar radius klinik ({$geofence['distance_m']}m dari pusat, maksimal {$geofence['radius_m']}m)",
                'geofence' => $geofence,
            ], 422);
        }

        $schedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $now = Carbon::now();
        $status = AttendanceService::determineClockOutStatus($now, $schedule);

        $photoPath = $request->file('photo')->store("attendances/{$user->id}/" . $today, 'public');

        $attendance->update([
            'clock_out_at' => $now,
            'clock_out_photo_path' => $photoPath,
            'clock_out_lat' => $request->lat,
            'clock_out_lng' => $request->lng,
            'clock_out_distance_m' => $geofence['distance_m'],
            'clock_out_accuracy_m' => $request->accuracy ? (int) $request->accuracy : null,
            'clock_out_status' => $status,
        ]);

        return response()->json([
            'message' => 'Clock out berhasil',
            'attendance' => $this->transform($attendance->fresh()),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $month = (int) ($request->month ?? Carbon::now()->month);
        $year = (int) ($request->year ?? Carbon::now()->year);

        $attendances = Attendance::where('user_id', $request->user()->id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->orderByDesc('work_date')
            ->paginate($request->per_page ?? 31);

        return response()->json([
            'data' => $attendances->getCollection()->map(fn ($a) => $this->transform($a))->values(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    private function transform(Attendance $a): array
    {
        return [
            'id' => $a->id,
            'work_date' => $a->work_date->toDateString(),
            'clock_in_at' => $a->clock_in_at?->toIso8601String(),
            'clock_in_status' => $a->clock_in_status,
            'clock_in_photo_url' => $a->clock_in_photo_path ? asset('storage/' . $a->clock_in_photo_path) : null,
            'clock_in_distance_m' => $a->clock_in_distance_m,
            'clock_out_at' => $a->clock_out_at?->toIso8601String(),
            'clock_out_status' => $a->clock_out_status,
            'clock_out_photo_url' => $a->clock_out_photo_path ? asset('storage/' . $a->clock_out_photo_path) : null,
            'clock_out_distance_m' => $a->clock_out_distance_m,
        ];
    }
}
