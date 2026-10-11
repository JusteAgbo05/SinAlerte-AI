<?php

namespace Tests\Unit;

use App\Services\ReportScorer;
use App\Support\Geo;
use PHPUnit\Framework\TestCase;

class ReportScorerTest extends TestCase
{
    public function test_base_score_depends_on_reported_severity(): void
    {
        $this->assertSame(35, ReportScorer::score('low', false, false, 0)['score']);
        $this->assertSame(55, ReportScorer::score('medium', false, false, 0)['score']);
        $this->assertSame(75, ReportScorer::score('high', false, false, 0)['score']);
    }

    public function test_photo_audio_and_neighbors_raise_the_score_with_explanations(): void
    {
        $result = ReportScorer::score('medium', true, true, 2);

        $this->assertSame(55 + 8 + 4 + 12, $result['score']);
        $this->assertSame('high', $result['level']);
        $this->assertCount(4, $result['reasons']);
    }

    public function test_score_is_capped_at_100_and_neighbor_bonus_at_18(): void
    {
        $this->assertSame(100, ReportScorer::score('high', true, true, 10)['score']);
        $this->assertSame(35 + 18, ReportScorer::score('low', false, false, 10)['score']);
    }

    public function test_unknown_severity_falls_back_to_low(): void
    {
        $this->assertSame(35, ReportScorer::score('???', false, false, 0)['score']);
    }

    public function test_levels(): void
    {
        $this->assertSame('high', ReportScorer::levelFor(75));
        $this->assertSame('medium', ReportScorer::levelFor(74));
        $this->assertSame('medium', ReportScorer::levelFor(55));
        $this->assertSame('low', ReportScorer::levelFor(54));
    }

    public function test_distance_is_close_to_known_values(): void
    {
        $this->assertEqualsWithDelta(0.0, Geo::distanceKm(6.37, 2.39, 6.37, 2.39), 0.0001);
        // 0,001° de latitude ≈ 111 m
        $this->assertEqualsWithDelta(0.111, Geo::distanceKm(6.370, 2.390, 6.371, 2.390), 0.002);
    }
}
