<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperatorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function operator(): User
    {
        return User::factory()->create([
            'email' => 'operateur@sinalerte.test',
            'password' => Hash::make('Secret-2026'),
        ]);
    }

    public function test_operator_can_log_in(): void
    {
        $this->operator();

        $this->post('/connexion', ['email' => 'operateur@sinalerte.test', 'password' => 'Secret-2026'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_email_case_does_not_matter(): void
    {
        $this->operator();

        // Majuscule automatique d'un clavier mobile
        $this->post('/connexion', ['email' => 'Operateur@Sinalerte.test', 'password' => 'Secret-2026'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_password_case_matters(): void
    {
        $this->operator();

        $this->post('/connexion', ['email' => 'operateur@sinalerte.test', 'password' => 'secret-2026'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_refused(): void
    {
        $this->operator();

        $this->post('/connexion', ['email' => 'operateur@sinalerte.test', 'password' => 'nope'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_create_command_makes_an_account_that_can_log_in(): void
    {
        $this->artisan('operator:create', ['email' => 'Nouveau@Sinalerte.test', 'password' => 'MotDePasse-1'])
            ->assertSuccessful();

        // Stocké en minuscules
        $this->assertDatabaseHas('users', ['email' => 'nouveau@sinalerte.test']);

        $this->post('/connexion', ['email' => 'nouveau@sinalerte.test', 'password' => 'MotDePasse-1'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_create_command_resets_the_password_of_an_existing_account(): void
    {
        $this->operator();

        $this->artisan('operator:create', ['email' => 'operateur@sinalerte.test', 'password' => 'Autre-Mdp-2026'])
            ->assertSuccessful();

        $this->assertSame(1, User::count());

        $this->post('/connexion', ['email' => 'operateur@sinalerte.test', 'password' => 'Secret-2026'])
            ->assertSessionHasErrors('email');
        $this->post('/connexion', ['email' => 'operateur@sinalerte.test', 'password' => 'Autre-Mdp-2026'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_create_command_refuses_a_short_password(): void
    {
        $this->artisan('operator:create', ['email' => 'court@sinalerte.test', 'password' => 'abc'])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'court@sinalerte.test']);
    }
}
