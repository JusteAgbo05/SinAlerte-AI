<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use App\Services\ReportIntake;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Compte opérateur. En production, définir OPERATOR_EMAIL / OPERATOR_PASSWORD dans .env.
        // L'e-mail est stocké en minuscules (la connexion le compare en minuscules).
        User::updateOrCreate(
            ['email' => Str::lower(trim((string) env('OPERATOR_EMAIL', 'operateur@sinalerte.test')))],
            [
                'name' => 'Opérateur SìnAlerte',
                'password' => Hash::make(env('OPERATOR_PASSWORD', 'ChangeMe-2026')),
            ],
        );

        // Équipes de démonstration (coordonnées approximatives, à remplacer par les vraies bases).
        $teams = [
            ['name' => 'Aqua Maintenance · Équipe 1', 'zone' => 'Cotonou centre', 'latitude' => 6.3654, 'longitude' => 2.4183],
            ['name' => 'Aqua Maintenance · Équipe 2', 'zone' => 'Cotonou ouest', 'latitude' => 6.3600, 'longitude' => 2.3850],
            ['name' => 'Aqua Maintenance · Équipe 3', 'zone' => 'Abomey-Calavi', 'latitude' => 6.4485, 'longitude' => 2.3558],
        ];
        foreach ($teams as $team) {
            Team::firstOrCreate(['name' => $team['name']], $team + ['is_available' => true]);
        }

        // Signalements de démonstration, uniquement en local.
        if (app()->environment('local') && Report::count() === 0) {
            $intake = app(ReportIntake::class);

            // Coordonnées approximatives, uniquement pour alimenter la démo.
            $intake->create([
                'perceived_severity' => 'high',
                'latitude' => 6.3640, 'longitude' => 2.4200,
                'location_hint' => 'Zongo, Cotonou',
            ]);
            $intake->create([
                'perceived_severity' => 'medium',
                'latitude' => 6.3672, 'longitude' => 2.4338,
                'location_hint' => 'Marché Dantokpa',
            ]);
            $intake->create([
                'perceived_severity' => 'low',
                'latitude' => 6.3590, 'longitude' => 2.3790,
                'location_hint' => 'Cadjèhoun, Cotonou',
            ]);
        }
    }
}
