<?php

namespace App\Support;

class HoraliaTimezones
{
    public const DEFAULT = 'America/Mexico_City';

    /** @return array<string, string> */
    public static function mexicoOptions(): array
    {
        return [
            'America/Mexico_City' => 'Ciudad de México (America/Mexico_City)',
            'America/Matamoros' => 'Acuña (America/Matamoros)',
            'America/Tijuana' => 'Tijuana (America/Tijuana)',
            'America/Cancun' => 'Cancún (America/Cancun)',
            'America/Hermosillo' => 'Hermosillo (America/Hermosillo)',
            'America/Chihuahua' => 'Chihuahua capital (America/Chihuahua)',
            'America/Ciudad_Juarez' => 'Ciudad Juárez (America/Ciudad_Juarez)',
            'America/Ojinaga' => 'Ojinaga (America/Ojinaga)',
            'America/Mazatlan' => 'Mazatlán (America/Mazatlan)',
            'America/Monterrey' => 'Monterrey (America/Monterrey)',
        ];
    }

    public static function forCenter(?string $timezone): string
    {
        return $timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)
            ? $timezone
            : self::DEFAULT;
    }
}
