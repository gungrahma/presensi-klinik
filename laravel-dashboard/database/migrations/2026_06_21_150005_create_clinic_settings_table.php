<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean', 'json'])->default('string');
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $defaults = [
            ['key' => 'app_name', 'value' => 'Absensi Klinik', 'type' => 'string', 'label' => 'Nama Aplikasi', 'description' => 'Nama aplikasi yang ditampilkan di PWA'],
            ['key' => 'clinic_name', 'value' => 'Klinik Sehat Sentosa', 'type' => 'string', 'label' => 'Nama Klinik', 'description' => 'Nama klinik yang ditampilkan'],
            ['key' => 'clinic_lat', 'value' => '-6.200000', 'type' => 'decimal', 'label' => 'Latitude Klinik', 'description' => 'Koordinat latitude klinik untuk validasi geofence'],
            ['key' => 'clinic_lng', 'value' => '106.816666', 'type' => 'decimal', 'label' => 'Longitude Klinik', 'description' => 'Koordinat longitude klinik untuk validasi geofence'],
            ['key' => 'radius_meter', 'value' => '100', 'type' => 'integer', 'label' => 'Radius (meter)', 'description' => 'Radius maksimal karyawan dari klinik untuk absen'],
            ['key' => 'late_tolerance_minutes', 'value' => '15', 'type' => 'integer', 'label' => 'Toleransi Telat (menit)', 'description' => 'Toleransi keterlambatan dalam menit'],
            ['key' => 'min_clock_out_minutes', 'value' => '240', 'type' => 'integer', 'label' => 'Minimal Durasi Kerja (menit)', 'description' => 'Durasi minimal kerja sebelum bisa clock out'],
        ];

        foreach ($defaults as $setting) {
            \DB::table('clinic_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
