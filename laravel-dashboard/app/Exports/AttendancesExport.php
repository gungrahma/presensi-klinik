<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendancesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(
        public string $from,
        public string $to,
    ) {}

    public function query()
    {
        return Attendance::query()
            ->with('user')
            ->whereBetween('work_date', [$this->from, $this->to])
            ->orderBy('work_date')
            ->orderBy('user_id');
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'NIK',
            'Nama Karyawan',
            'Jabatan',
            'Jam Masuk',
            'Status Masuk',
            'Jarak Masuk (m)',
            'Jam Pulang',
            'Status Pulang',
            'Jarak Pulang (m)',
            'IP Address',
        ];
    }

    public function map($row): array
    {
        return [
            $row->work_date->format('d/m/Y'),
            $row->user?->employee_id ?? '-',
            $row->user?->name ?? '-',
            $row->user?->position ?? '-',
            $row->clock_in_at?->format('H:i') ?? '-',
            match ($row->clock_in_status) {
                'on_time' => 'Tepat',
                'late' => 'Telat',
                default => '-',
            },
            $row->clock_in_distance_m ?? '-',
            $row->clock_out_at?->format('H:i') ?? '-',
            match ($row->clock_out_status) {
                'on_time' => 'Tepat',
                'early' => 'Pulang Cepat',
                default => '-',
            },
            $row->clock_out_distance_m ?? '-',
            $row->ip_address ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
