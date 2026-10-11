<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des équipes de terrain proposées lors de l'affectation d'un signalement.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::withCount([
            // Interventions en cours : signalements envoyés à l'équipe et pas encore terminés.
            'reports as active_count' => fn ($q) => $q->where('status', Report::STATUS_DISPATCHED),
            'reports as total_count',
        ])->orderBy('name')->get();

        return Inertia::render('Operator/Teams', [
            'teams' => $teams->map(fn (Team $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'zone' => $t->zone,
                'latitude' => $t->latitude,
                'longitude' => $t->longitude,
                'is_available' => $t->is_available,
                'active_count' => (int) $t->active_count,
                'total_count' => (int) $t->total_count,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Team::create($request->validate($this->rules(), $this->messages()));

        return back();
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        // "sometimes" : on peut n'envoyer qu'un seul champ (ex. basculer la disponibilité).
        $team->update($request->validate($this->rules($team), $this->messages()));

        return back();
    }

    public function destroy(Team $team): RedirectResponse
    {
        // Les signalements gardent le nom de l'équipe dans leur historique : on ne supprime
        // une équipe que si elle n'a jamais été affectée. Sinon, on la rend indisponible.
        if ($team->reports()->exists()) {
            return back()->withErrors([
                'team' => "« {$team->name} » a un historique d'interventions : elle ne peut pas être supprimée. "
                    ."Rendez-la plutôt indisponible, elle ne sera plus proposée.",
            ]);
        }

        $team->delete();

        return back();
    }

    /** @return array<string, mixed> */
    private function rules(?Team $team = null): array
    {
        $required = $team ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$required, 'string', 'max:80', Rule::unique('teams', 'name')->ignore($team?->id)],
            'zone' => ['nullable', 'string', 'max:80'],
            // Position de la base : sert à proposer l'équipe la plus proche d'une fuite.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => "Indiquez le nom de l'équipe.",
            'name.unique' => 'Une équipe porte déjà ce nom.',
            'name.max' => 'Le nom est trop long (80 caractères maximum).',
            'latitude.numeric' => 'Latitude invalide.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'latitude.required_with' => 'Renseignez aussi la latitude.',
            'longitude.numeric' => 'Longitude invalide.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'longitude.required_with' => 'Renseignez aussi la longitude.',
        ];
    }
}
