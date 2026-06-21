<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $schedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (!$schedule) {
            return response()->json([
                'has_schedule' => false,
                'schedule' => null,
            ]);
        }

        return response()->json([
            'has_schedule' => true,
            'schedule' => [
                'id' => $schedule->id,
                'work_date' => $schedule->work_date->toDateString(),
                'is_off' => $schedule->is_off,
                'notes' => $schedule->notes,
                'shift' => $schedule->shift ? [
                    'id' => $schedule->shift->id,
                    'name' => $schedule->shift->name,
                    'start_time' => $schedule->shift->start_time,
                    'end_time' => $schedule->shift->end_time,
                    'tolerance_minutes' => $schedule->shift->tolerance_minutes,
                ] : null,
            ],
        ]);
    }
}
