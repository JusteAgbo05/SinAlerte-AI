<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOperator extends Command
{
    protected $signature = 'operator:create
        {email : Adresse e-mail de connexion}
        {password? : Mot de passe (demandé à l\'écran si omis)}
        {--name=Opérateur : Nom affiché (uniquement à la création)}';

    protected $description = "Crée un compte opérateur, ou réinitialise son mot de passe s'il existe déjà";

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $password = (string) ($this->argument('password') ?? $this->secret('Mot de passe (8 caractères minimum)'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse e-mail invalide.');

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = (string) $this->option('name');
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info(($user->wasRecentlyCreated ? 'Compte créé' : 'Mot de passe réinitialisé').' : '.$email);

        return self::SUCCESS;
    }
}
