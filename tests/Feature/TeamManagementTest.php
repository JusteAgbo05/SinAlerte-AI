<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function operator(): User
    {
        return User::factory()->create();
    }

    private function team(string $name = 'Équipe test', bool $available = true): Team
    {
        return Team::create(['name' => $name, 'is_available' => $available]);
    }

    private function newReport(): Report
    {
        $this->post('/signaler', ['perceived_severity' => 'high', 'latitude' => 6.3703, 'longitude' => 2.3912]);

        return Report::latest('id')->firstOrFail();
    }

    private function assignTo(User $operator, Report $report, Team $team)
    {
        return $this->actingAs($operator)->patch(
            "/operateur/signalements/{$report->id}",
            ['action' => 'assign', 'team_id' => $team->id],
        );
    }

    // --- Gestion des équipes -------------------------------------------------

    public function test_teams_page_requires_login(): void
    {
        $this->get('/operateur/equipes')->assertRedirect(route('login'));
    }

    public function test_operator_sees_the_teams(): void
    {
        $this->team('Équipe A');
        $this->team('Équipe B');

        $this->actingAs($this->operator())
            ->get('/operateur/equipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operator/Teams')
                ->has('teams', 2));
    }

    public function test_operator_can_create_a_team(): void
    {
        $this->actingAs($this->operator())
            ->post('/operateur/equipes', [
                'name' => 'Équipe Nord',
                'zone' => 'Abomey-Calavi',
                'latitude' => 6.4485,
                'longitude' => 2.3558,
                'is_available' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teams', ['name' => 'Équipe Nord', 'zone' => 'Abomey-Calavi']);
    }

    public function test_a_team_needs_a_name_and_a_complete_position(): void
    {
        $op = $this->operator();

        $this->actingAs($op)->post('/operateur/equipes', ['zone' => 'Sans nom'])
            ->assertSessionHasErrors('name');

        $this->actingAs($op)->post('/operateur/equipes', ['name' => 'Demi-position', 'longitude' => 2.4])
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('teams', 0);
    }

    public function test_team_names_must_be_unique(): void
    {
        $this->team('Équipe A');

        $this->actingAs($this->operator())
            ->post('/operateur/equipes', ['name' => 'Équipe A'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('teams', 1);
    }

    public function test_a_team_can_be_edited_without_changing_its_name(): void
    {
        $team = $this->team('Équipe A');

        $this->actingAs($this->operator())
            ->patch("/operateur/equipes/{$team->id}", ['name' => 'Équipe A', 'zone' => 'Cotonou nord'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Cotonou nord', $team->fresh()->zone);
    }

    public function test_availability_can_be_toggled_alone(): void
    {
        $team = $this->team('Équipe A');
        $op = $this->operator();

        $this->actingAs($op)->patch("/operateur/equipes/{$team->id}", ['is_available' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($team->fresh()->is_available);

        $this->actingAs($op)->patch("/operateur/equipes/{$team->id}", ['is_available' => true]);
        $this->assertTrue($team->fresh()->is_available);
    }

    public function test_a_team_that_never_worked_can_be_deleted(): void
    {
        $team = $this->team();

        $this->actingAs($this->operator())->delete("/operateur/equipes/{$team->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_a_team_with_history_cannot_be_deleted(): void
    {
        $op = $this->operator();
        $team = $this->team();
        $this->assignTo($op, $this->newReport(), $team);

        $this->actingAs($op)->delete("/operateur/equipes/{$team->id}")
            ->assertSessionHasErrors('team');

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    // --- Affectation et changement d'équipe -----------------------------------

    public function test_assigning_a_team_dispatches_the_report(): void
    {
        $op = $this->operator();
        $team = $this->team();
        $report = $this->newReport();

        $this->assignTo($op, $report, $team)->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame(Report::STATUS_DISPATCHED, $report->status);
        $this->assertSame($team->id, $report->team_id);
        $this->assertTrue($report->events()->where('note', 'like', 'Équipe envoyée%')->exists());
    }

    public function test_an_unavailable_team_cannot_be_assigned(): void
    {
        $op = $this->operator();
        $team = $this->team('Équipe en pause', available: false);
        $report = $this->newReport();

        $this->assignTo($op, $report, $team)->assertSessionHasErrors('team_id');

        $this->assertNull($report->fresh()->team_id);
        $this->assertNotSame(Report::STATUS_DISPATCHED, $report->fresh()->status);
    }

    public function test_the_team_of_a_dispatched_report_can_be_changed(): void
    {
        $op = $this->operator();
        $a = $this->team('Équipe A');
        $b = $this->team('Équipe B');
        $report = $this->newReport();

        $this->assignTo($op, $report, $a);
        $firstDispatch = $report->fresh()->dispatched_at;
        $this->assignTo($op, $report, $b)->assertSessionHasNoErrors();

        $report->refresh();
        $this->assertSame($b->id, $report->team_id);
        $this->assertSame(Report::STATUS_DISPATCHED, $report->status);
        // Le délai de prise en charge reste celui du premier envoi.
        $this->assertEquals($firstDispatch, $report->dispatched_at);
        $this->assertTrue($report->events()->where('note', 'like', 'Équipe changée%')->exists());
    }

    public function test_choosing_the_same_team_again_adds_nothing(): void
    {
        $op = $this->operator();
        $team = $this->team();
        $report = $this->newReport();

        $this->assignTo($op, $report, $team);
        $events = $report->events()->count();

        $this->assignTo($op, $report, $team);

        $this->assertSame($events, $report->events()->count());
    }

    public function test_the_citizen_sees_the_new_team(): void
    {
        $op = $this->operator();
        $a = $this->team('Équipe A');
        $b = $this->team('Équipe B');
        $report = $this->newReport();

        $this->assignTo($op, $report, $a);
        $this->assignTo($op, $report, $b);

        $this->get(route('tracking.show', $report->code))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Track/Show')
                ->where('report.team.name', 'Équipe B'));
    }

    public function test_dashboard_proposes_another_team_when_changing(): void
    {
        $op = $this->operator();
        $a = Team::create(['name' => 'Équipe A', 'is_available' => true, 'latitude' => 6.3703, 'longitude' => 2.3912]);
        $b = Team::create(['name' => 'Équipe B', 'is_available' => true, 'latitude' => 6.45, 'longitude' => 2.36]);
        $report = $this->newReport();
        $this->assignTo($op, $report, $a);

        // A est la plus proche, mais c'est l'équipe actuelle : on propose B.
        $this->actingAs($op)->get('/operateur?selected='.$report->code)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operator/Dashboard')
                ->where('selected.team_id', $a->id)
                ->where('selected.suggested_team_id', $b->id));
    }
}
