<?php

namespace App\Services;

/**
 * Score de priorité d'un signalement (0-100), version 1 : à base de règles explicables.
 *
 * Cette classe est volontairement pure (aucune base de données, aucun framework) :
 * elle est facile à tester et pourra être remplacée ou complétée plus tard par une
 * analyse d'image (IA) sans toucher au reste de l'application.
 */
final class ReportScorer
{
    /** Rayon (km) dans lequel deux signalements ouverts sont considérés comme "au même endroit". */
    public const NEARBY_RADIUS_KM = 0.2;

    public const LEVEL_HIGH = 'high';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_LOW = 'low';

    private const BASE = [
        'low' => 35,
        'medium' => 55,
        'high' => 75,
    ];

    /** Libellés pour les phrases d'explication. */
    private const DESCRIBED = [
        'low' => 'petite fuite (goutte à goutte)',
        'medium' => 'écoulement continu',
        'high' => 'fort jaillissement',
    ];

    /**
     * @return array{score:int, level:string, reasons:list<string>}
     */
    public static function score(string $perceived, bool $hasPhoto, bool $hasAudio, int $nearbyOpen): array
    {
        if (! array_key_exists($perceived, self::BASE)) {
            $perceived = 'low';
        }

        $score = self::BASE[$perceived];
        $reasons = ['Gravité signalée : '.self::DESCRIBED[$perceived]];

        if ($hasPhoto) {
            $score += 8;
            $reasons[] = 'Photo jointe : situation vérifiable à distance';
        }

        if ($hasAudio) {
            $score += 4;
            $reasons[] = 'Note vocale jointe : contexte supplémentaire';
        }

        if ($nearbyOpen > 0) {
            $score += min(18, 6 * $nearbyOpen);
            $reasons[] = sprintf(
                '%d autre%s signalement%s ouvert%s à moins de 200 m',
                $nearbyOpen,
                $nearbyOpen > 1 ? 's' : '',
                $nearbyOpen > 1 ? 's' : '',
                $nearbyOpen > 1 ? 's' : '',
            );
        }

        $score = min(100, $score);

        return [
            'score' => $score,
            'level' => self::levelFor($score),
            'reasons' => $reasons,
        ];
    }

    public static function levelFor(int $score): string
    {
        return match (true) {
            $score >= 75 => self::LEVEL_HIGH,
            $score >= 55 => self::LEVEL_MEDIUM,
            default => self::LEVEL_LOW,
        };
    }
}
