<?php

namespace App\Services;

use App\Models\Personal;

class AttendanceLocationService
{
    public function parseCoordinates(?string $coordinates): ?array
    {
        if (!$coordinates || !preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $coordinates, $matches)) {
            return null;
        }

        $latitude = (float) $matches[1];
        $longitude = (float) $matches[2];
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [$latitude, $longitude];
    }

    public function rangeFor(Personal $personal, float $latitude, float $longitude): ?string
    {
        $centerCoordinates = $this->parseCoordinates($personal->centro?->geolocalizacion);
        if (!$centerCoordinates) {
            return null;
        }

        [$centerLatitude, $centerLongitude] = $centerCoordinates;
        $distance = $this->distanceInMeters($latitude, $longitude, $centerLatitude, $centerLongitude);
        $tolerance = (int) ($personal->empresa?->metros_distancia_entrada ?? 0);

        return $distance <= $tolerance ? 'En rango' : 'Fuera de rango';
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $deltaLatitude = deg2rad($lat2 - $lat1);
        $deltaLongitude = deg2rad($lon2 - $lon1);
        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLongitude / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }
}
