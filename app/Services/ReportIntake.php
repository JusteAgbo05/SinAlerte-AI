<?php

namespace App\Services;

use App\Models\Report;
use App\Support\Geo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Enregistre un signalement citoyen : fichiers, score de priorité, frise de suivi.
 */
class ReportIntake
{
    /** Alphabet sans caractères ambigus (pas de 0/O, 1/I/L) pour des codes faciles à dicter. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  array<string, mixed>  $data  perceived_severity (requis), latitude, longitude,
     *                                      gps_accuracy_m, location_hint, audio_lang
     */
    public function create(array $data, ?UploadedFile $photo = null, ?UploadedFile $audio = null): Report
    {
        return DB::transaction(function () use ($data, $photo, $audio) {
            $report = new Report([
                'perceived_severity' => $data['perceived_severity'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'gps_accuracy_m' => $data['gps_accuracy_m'] ?? null,
                'location_hint' => $data['location_hint'] ?? null,
            ]);

            $report->code = $this->uniqueCode();
            $report->status = Report::STATUS_PRIORITIZED;

            // Fichiers privés : ils ne sont servis qu'aux opérateurs connectés.
            $report->photo_path = $photo?->store('reports/photos', 'local');
            $report->audio_path = $audio?->store('reports/audio', 'local');
            $report->audio_lang = $audio ? ($data['audio_lang'] ?? 'fr') : null;

            $this->applyScore($report);
            $report->save();

            $report->events()->create([
                'status' => Report::STATUS_RECEIVED,
                'note' => 'Signalement reçu',
            ]);
            $report->events()->create([
                'status' => Report::STATUS_PRIORITIZED,
                'note' => "Priorité calculée : {$report->score}/100",
            ]);

            // Un nouveau signalement au même endroit renforce la priorité des voisins.
            foreach ($this->openNeighbors($report) as $neighbor) {
                $this->applyScore($neighbor);
                $neighbor->save();
            }

            return $report;
        });
    }

    public function applyScore(Report $report): void
    {
        $result = ReportScorer::score(
            $report->perceived_severity,
            filled($report->photo_path),
            filled($report->audio_path),
            $this->openNeighbors($report)->count(),
        );

        $report->score = $result['score'];
        $report->level = $result['level'];
        $report->score_reasons = $result['reasons'];
    }

    /**
     * Signalements encore ouverts, créés ces 7 derniers jours, à moins de 200 m.
     *
     * @return Collection<int, Report>
     */
    public function openNeighbors(Report $report): Collection
    {
        if (! $report->hasCoordinates()) {
            return new Collection();
        }

        $lat = (float) $report->latitude;
        $lon = (float) $report->longitude;
        $box = 0.003; // pré-filtre rapide (~330 m) avant le calcul exact

        return Report::query()
            ->when($report->exists, fn ($q) => $q->whereKeyNot($report->getKey()))
            ->whereIn('status', Report::OPEN_STATUSES)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereBetween('latitude', [$lat - $box, $lat + $box])
            ->whereBetween('longitude', [$lon - $box, $lon + $box])
            ->get()
            ->filter(fn (Report $n) => Geo::distanceKm($lat, $lon, (float) $n->latitude, (float) $n->longitude)
                <= ReportScorer::NEARBY_RADIUS_KM)
            ->values();
    }

    private function uniqueCode(): string
    {
        $max = strlen(self::CODE_ALPHABET) - 1;

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::CODE_ALPHABET[random_int(0, $max)];
            }
            $code = 'SA-'.$suffix;
        } while (Report::where('code', $code)->exists());

        return $code;
    }
}
