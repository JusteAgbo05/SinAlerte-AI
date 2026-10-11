<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les pages Inertia utilisent @vite : inutile d'avoir compilé les assets pour tester.
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_citizen_can_report_a_leak_and_track_it(): void
    {
        $response = $this->post('/signaler', [
            'perceived_severity' => 'high',
            'latitude' => 6.3703,
            'longitude' => 2.3912,
            'photo' => UploadedFile::fake()->image('fuite.jpg'),
        ]);

        $report = Report::firstOrFail();

        $response->assertRedirect(route('tracking.show', $report->code));
        $this->assertSame(Report::STATUS_PRIORITIZED, $report->status);
        $this->assertSame('high', $report->level);
        $this->assertCount(2, $report->events);
        Storage::disk('local')->assertExists($report->photo_path);

        $this->get(route('tracking.show', $report->code))->assertOk();
    }

    public function test_a_position_or_a_hint_is_required(): void
    {
        $this->post('/signaler', ['perceived_severity' => 'low'])
            ->assertSessionHasErrors('latitude');

        $this->post('/signaler', [
            'perceived_severity' => 'low',
            'location_hint' => 'Face à la pharmacie, Tankpè',
        ])->assertSessionHasNoErrors();
    }

    public function test_a_second_report_at_the_same_place_raises_the_first_one(): void
    {
        $payload = ['perceived_severity' => 'medium', 'latitude' => 6.3640, 'longitude' => 2.4200];

        $this->post('/signaler', $payload);
        $first = Report::firstOrFail();
        $before = $first->score;

        $this->post('/signaler', $payload);

        $this->assertGreaterThan($before, $first->fresh()->score);
    }

    public function test_unknown_tracking_code_goes_back_with_an_error(): void
    {
        $this->post('/suivi', ['code' => 'SA-ZZZZZZ'])->assertSessionHasErrors('code');
    }

    public function test_operator_area_requires_login(): void
    {
        $this->get('/operateur')->assertRedirect(route('login'));
    }

    public function test_operator_can_dispatch_then_resolve(): void
    {
        $this->post('/signaler', [
            'perceived_severity' => 'high',
            'latitude' => 6.3703,
            'longitude' => 2.3912,
        ]);
        $report = Report::firstOrFail();
        $team = Team::create(['name' => 'Équipe test', 'is_available' => true]);
        $operator = User::factory()->create();

        $this->actingAs($operator)->get('/operateur')->assertOk();

        $this->actingAs($operator)
            ->patch("/operateur/signalements/{$report->id}", ['action' => 'assign', 'team_id' => $team->id])
            ->assertRedirect();
        $this->assertSame(Report::STATUS_DISPATCHED, $report->fresh()->status);
        $this->assertSame($team->id, $report->fresh()->team_id);

        $this->actingAs($operator)
            ->patch("/operateur/signalements/{$report->id}", ['action' => 'resolve'])
            ->assertRedirect();
        $this->assertSame(Report::STATUS_RESOLVED, $report->fresh()->status);
    }
}
