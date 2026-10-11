<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Team;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Espace opérateur : liste priorisée, carte, affectation d'équipe, clôture.
 */
class DashboardController extends Controller
{
    private const FILTERS = ['all', 'urgent', 'to_assign', 'resolved'];

    public function index(Request $request): Response
    {
        $filter = in_array($request->query('filter'), self::FILTERS, true)
            ? $request->query('filter')
            : 'all';

        $reports = $this->queryFor($filter)
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $selected = null;
        if ($code = $request->query('selected')) {
            $selected = Report::where('code', (string) $code)->first();
        }
        $selected ??= $reports->first();
        $selected?->loadMissing(['team', 'events']);

        // Marqueurs : tous les signalements ouverts localisés (indépendamment du filtre).
        $markers = Report::whereIn('status', Report::OPEN_STATUSES)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Report $r) => [
                'code' => $r->code,
                'lat' => $r->latitude,
                'lng' => $r->longitude,
                'level' => $r->levelColor(),
            ])
            ->values();

        return Inertia::render('Operator/Dashboard', [
            'filter' => $filter,
            'today' => now()->timezone(Report::DISPLAY_TZ)->locale('fr')->translatedFormat('l j F'),
            'counts' => $this->counts(),
            'metrics' => $this->metrics(),
            'reports' => $reports->map(fn (Report $r) => $this->row($r))->values(),
            'markers' => $markers,
            'selected' => $selected ? $this->detail($selected) : null,
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:assign,resolve'],
            'team_id' => ['required_if:action,assign', 'nullable', 'exists:teams,id'],
        ]);

        if ($data['action'] === 'assign') {
            abort_if($report->status === Report::STATUS_RESOLVED, 422, 'Ce signalement est déjà terminé.');

            $team = Team::findOrFail($data['team_id']);

            $report->update([
                'team_id' => $team->id,
                'status' => Report::STATUS_DISPATCHED,
                'dispatched_at' => $report->dispatched_at ?? now(),
            ]);
            $report->events()->create([
                'status' => Report::STATUS_DISPATCHED,
                'note' => "Équipe envoyée : {$team->name}",
            ]);
        } else {
            $report->update([
                'status' => Report::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);
            $report->events()->create([
                'status' => Report::STATUS_RESOLVED,
                'note' => 'Intervention terminée',
            ]);
        }

        return back();
    }

    public function photo(Report $report): BinaryFileResponse
    {
        return $this->media($report->photo_path);
    }

    public function audio(Report $report): BinaryFileResponse
    {
        return $this->media($report->audio_path);
    }

    // ------------------------------------------------------------------

    private function media(?string $path): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        // response()->file gère les requêtes "Range" (nécessaires à la lecture audio sur Safari).
        return response()->file($disk->path($path), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function queryFor(string $filter): Builder
    {
        return match ($filter) {
            'urgent' => Report::whereIn('status', Report::OPEN_STATUSES)->where('level', 'high'),
            'to_assign' => Report::whereIn('status', [Report::STATUS_RECEIVED, Report::STATUS_PRIORITIZED]),
            'resolved' => Report::where('status', Report::STATUS_RESOLVED)
                ->where('resolved_at', '>=', now()->subDays(30)),
            default => Report::whereIn('status', Report::OPEN_STATUSES),
        };
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'all' => $this->queryFor('all')->count(),
            'urgent' => $this->queryFor('urgent')->count(),
            'to_assign' => $this->queryFor('to_assign')->count(),
            'resolved' => $this->queryFor('resolved')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function metrics(): array
    {
        $dispatched = Report::whereNotNull('dispatched_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['created_at', 'dispatched_at']);

        $avgMinutes = $dispatched->isEmpty()
            ? null
            : (int) round($dispatched->avg(fn ($r) => abs($r->created_at->diffInMinutes($r->dispatched_at))));

        return [
            'new_today' => Report::where('created_at', '>=', now()->timezone(Report::DISPLAY_TZ)->startOfDay())->count(),
            'avg_dispatch_minutes' => $avgMinutes,
            'resolved_month' => Report::where('status', Report::STATUS_RESOLVED)
                ->where('resolved_at', '>=', now()->timezone(Report::DISPLAY_TZ)->startOfMonth())
                ->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function row(Report $r): array
    {
        return [
            'id' => $r->id,
            'code' => $r->code,
            'title' => $r->title(),
            'place' => $r->place(),
            'score' => $r->score,
            'color' => $r->levelColor(),
            'status' => $r->status,
            'status_label' => $r->statusLabel(),
            'ago' => $r->ago(),
        ];
    }

    /** @return array<string, mixed> */
    private function detail(Report $r): array
    {
        $teams = Team::orderBy('name')->get()->map(function (Team $t) use ($r) {
            $distance = ($r->hasCoordinates() && $t->latitude !== null && $t->longitude !== null)
                ? round(Geo::distanceKm($r->latitude, $r->longitude, $t->latitude, $t->longitude), 1)
                : null;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'zone' => $t->zone,
                'is_available' => $t->is_available,
                'distance_km' => $distance,
            ];
        });

        // Équipe suggérée : la plus proche parmi les équipes disponibles.
        $suggested = $teams->where('is_available', true)->whereNotNull('distance_km')->sortBy('distance_km')->first()
            ?? $teams->where('is_available', true)->first();

        return $this->row($r) + [
            'level_label' => $r->levelLabel(),
            'reasons' => $r->score_reasons ?? [],
            'lat' => $r->latitude,
            'lng' => $r->longitude,
            'gps_accuracy_m' => $r->gps_accuracy_m,
            'created_at' => Report::formatDate($r->created_at),
            'photo_url' => $r->photo_path ? route('operator.reports.photo', $r) : null,
            'audio_url' => $r->audio_path ? route('operator.reports.audio', $r) : null,
            'audio_lang' => $r->audio_lang === 'fon' ? 'Fɔngbè' : ($r->audio_lang ? 'Français' : null),
            'team' => $r->team?->name,
            'teams' => $teams->values(),
            'suggested_team_id' => $suggested['id'] ?? null,
            'events' => $r->events->map(fn ($e) => [
                'note' => $e->note,
                'at' => Report::formatDate($e->created_at),
            ])->values(),
        ];
    }
}
