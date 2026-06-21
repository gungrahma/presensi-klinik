<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $leaves = LeaveRequest::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($l) => $this->transform($l));

        return response()->json(['data' => $leaves]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:cuti,izin,sakit'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $totalDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store("leave-attachments/{$request->user()->id}", 'public');
        }

        $leave = LeaveRequest::create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $totalDays,
            'reason' => $data['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan cuti/izin berhasil dikirim, menunggu persetujuan admin',
            'leave_request' => $this->transform($leave),
        ], 201);
    }

    private function transform(LeaveRequest $l): array
    {
        return [
            'id' => $l->id,
            'type' => $l->type,
            'start_date' => $l->start_date->toDateString(),
            'end_date' => $l->end_date->toDateString(),
            'total_days' => $l->total_days,
            'reason' => $l->reason,
            'attachment_url' => $l->attachment_path ? asset('storage/' . $l->attachment_path) : null,
            'status' => $l->status,
            'admin_note' => $l->admin_note,
            'created_at' => $l->created_at->toIso8601String(),
            'approved_at' => $l->approved_at?->toIso8601String(),
        ];
    }
}
