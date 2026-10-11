<?php

namespace App\Http\Controllers;

use App\Services\ReportIntake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parcours citoyen : créer un signalement (sans compte).
 */
class ReportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Report/Create');
    }

    public function store(Request $request, ReportIntake $intake): RedirectResponse
    {
        $data = $request->validate([
            'perceived_severity' => ['required', 'in:low,medium,high'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'gps_accuracy_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'location_hint' => ['nullable', 'string', 'max:160'],
            'audio_lang' => ['nullable', 'in:fr,fon'],
            'photo' => [
                'nullable', 'file', 'max:8192',
                'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif',
            ],
            'audio' => [
                'nullable', 'file', 'max:6144',
                // MediaRecorder produit du webm (Chrome/Firefox) ou du mp4/m4a (Safari).
                'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/wav,audio/x-wav',
            ],
        ], [
            'perceived_severity.required' => 'Indiquez la gravité de la fuite.',
            'photo.max' => 'La photo est trop lourde (8 Mo maximum).',
            'photo.mimetypes' => 'Format de photo non pris en charge (JPEG, PNG, WebP ou HEIC).',
            'audio.max' => 'La note vocale est trop lourde.',
            'audio.mimetypes' => 'Format audio non pris en charge.',
        ]);

        // Les équipes doivent pouvoir trouver la fuite : position GPS ou, à défaut, un repère écrit.
        if (blank($data['latitude'] ?? null) && blank($data['location_hint'] ?? null)) {
            throw ValidationException::withMessages([
                'latitude' => 'Indiquez votre position, ou à défaut un repère (quartier, rue, bâtiment).',
            ]);
        }

        $report = $intake->create($data, $request->file('photo'), $request->file('audio'));

        return redirect()
            ->route('tracking.show', $report->code)
            ->with('created', true);
    }
}
