<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Connexion des opérateurs. Il n'y a volontairement pas d'inscription publique :
 * les comptes opérateurs sont créés par l'équipe : `php artisan operator:create <email> <mot de passe>`
 * (crée le compte, ou réinitialise son mot de passe s'il existe déjà).
 */
class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Les e-mails sont enregistrés en minuscules. SQLite (base par défaut de Laravel) distingue
        // majuscules et minuscules : sans cette normalisation, la majuscule automatique d'un clavier
        // mobile ("Operateur@...") donne "Identifiants incorrects". Le mot de passe, lui, n'est jamais modifié.
        $credentials['email'] = Str::lower(trim($credentials['email']));

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'E-mail ou mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
