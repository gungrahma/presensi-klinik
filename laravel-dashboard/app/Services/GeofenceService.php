<?php

namespace App\Services;

use App\Models\ClinicSetting;

class GeofenceService
{
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    public static function isWithinRadius(float $lat, float $lng): array
    {
        $clinicLat = (float) ClinicSetting::get('clinic_lat', 0);
        $clinicLng = (float) ClinicSetting::get('clinic_lng', 0);
        $radius = (int) ClinicSetting::get('radius_meter', 100);

        $distance = self::haversineDistance($lat, $lng, $clinicLat, $clinicLng);

        return [
            'within' => $distance <= $radius,
            'distance_m' => (int) $distance,
            'radius_m' => $radius,
        ];
    }
}
