<?php

namespace Database\Seeders;

use App\Models\ClinicSetting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@absensiklinik.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'budi@absensiklinik.test'],
            [
                'name' => 'Budi Santoso',
                'employee_id' => 'KRY-001',
                'position' => 'Perawat',
                'phone' => '081234567890',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'siti@absensiklinik.test'],
            [
                'name' => 'Siti Aminah',
                'employee_id' => 'KRY-002',
                'position' => 'Dokter',
                'phone' => '081234567891',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'dewi@absensiklinik.test'],
            [
                'name' => 'Dewi Lestari',
                'employee_id' => 'KRY-003',
                'position' => 'Resepsionis',
                'phone' => '081234567892',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        Shift::updateOrCreate(
            ['name' => 'Shift Pagi'],
            [
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'tolerance_minutes' => 15,
                'is_active' => true,
            ],
        );

        Shift::updateOrCreate(
            ['name' => 'Shift Siang'],
            [
                'start_time' => '12:00:00',
                'end_time' => '20:00:00',
                'tolerance_minutes' => 15,
                'is_active' => true,
            ],
        );

        Shift::updateOrCreate(
            ['name' => 'Shift Malam'],
            [
                'start_time' => '20:00:00',
                'end_time' => '04:00:00',
                'tolerance_minutes' => 15,
                'is_active' => true,
            ],
        );
    }
}
