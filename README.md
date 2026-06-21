# Absensi Klinik — PWA + Laravel + Filament

Sistem absensi karyawan klinik dengan:
- **PWA** (Vite + React + TypeScript) untuk karyawan (mobile-first, offline-capable, installable)
- **Laravel 13 + Filament v4** untuk admin dashboard

## Struktur

```
absensi-klinik/
├── laravel-dashboard/    # Backend API + Admin panel
└── pwa/                  # PWA untuk karyawan
```

## Akun Default

| Role | Email | Password |
|---|---|---|
| Admin | `admin@absensiklinik.test` | `password` |
| Karyawan | `budi@absensiklinik.test` | `password` |
| Karyawan | `siti@absensiklinik.test` | `password` |
| Karyawan | `dewi@absensiklinik.test` | `password` |

## Quick Start (Development)

```bash
# Terminal 1: Laravel backend
cd laravel-dashboard
php artisan serve --host=0.0.0.0 --port=8000
# Akses: http://localhost:8000/admin

# Terminal 2: PWA
cd pwa
npm run dev
# Akses: http://localhost:5173
```

URL penting:
- **Admin Dashboard**: http://localhost:8000/admin
- **PWA Karyawan**: http://localhost:5173
- **API**: http://localhost:8000/api

Lihat [DEPLOYMENT.md](DEPLOYMENT.md) untuk deploy ke production.
Lihat [CHECKLIST.md](CHECKLIST.md) untuk pre-launch checklist.
