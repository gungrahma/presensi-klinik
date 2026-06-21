<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_in_photo_path',
        'clock_in_lat',
        'clock_in_lng',
        'clock_in_distance_m',
        'clock_in_accuracy_m',
        'clock_in_status',
        'clock_out_at',
        'clock_out_photo_path',
        'clock_out_lat',
        'clock_out_lng',
        'clock_out_distance_m',
        'clock_out_accuracy_m',
        'clock_out_status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
