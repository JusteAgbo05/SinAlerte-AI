<?php

namespace App\Support;

final class Geo
{
    private const EARTH_RADIUS_KM = 6371.0088;

    /**
     * Distance à vol d'oiseau entre deux points GPS (formule de Haversine), en kilomètres.
     */
    public static function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }
}
