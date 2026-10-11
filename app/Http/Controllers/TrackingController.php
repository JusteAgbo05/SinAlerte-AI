<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suivi citoyen par code. La page ne montre ni photo, ni audio, ni position précise.
 */
class TrackingController extends Controller
{
    private const NOT_FOUND = 'Aucun signalement ne correspond à ce code.';

    public function lookup(): Response
    {
        return Inertia::render('Track/Lookup');
    }

    public function find(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);

        $code = strtoupper(preg_replace('/\s+/', '', $data['code']));

        if (! Report::where('code', $code)->exists()) {
            return back()->withErrors(['code' => self::NOT_FOUND]);
        }

        return redirect()->route('tracking.show', $code);
    }

    public function show(string $code): Response|RedirectResponse
    {
        $report = Report::with(['events', 'team'])
            ->where('code', strtoupper($code))
            ->first();

        if (! $report) {
            return redirect()->route('tracking.lookup')->withErrors(['code' => self::NOT_FOUND]);
        }

        return Inertia::render('Track/Show', [
            'justCreated' => (bool) session('created', false),
            'report' => [
                'code' => $report->code,
                'status' => $report->status,
                'status_index' => $report->statusIndex(),
                'level' => $report->level,
                'level_label' => $report->levelLabel(),
                'place' => $report->location_hint,
                'created_at' => Report::formatDate($report->created_at),
                'team' => $report->team ? [
                    'name' => $report->team->name,
                    'initials' => $report->team->initials(),
                ] : null,
                // Horodatage de chaque étape atteinte
                'steps' => $report->events->map(fn ($e) => [
                    'status' => $e->status,
                    'at' => Report::formatDate($e->created_at),
                ])->values(),
            ],
        ]);
    }
}
