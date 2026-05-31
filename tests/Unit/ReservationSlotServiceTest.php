<?php

namespace Tests\Unit;

use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationSlotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_reservation_blocks_adjacent_buffer_slots(): void
    {
        Carbon::setTestNow('2026-05-26 08:00:00');

        $user = User::factory()->create();

        Reservation::query()->create([
            'user_id' => $user->id,
            'reserved_on' => '2026-05-26',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $service = app(ReservationSlotService::class);
        $slots = collect($service->slotsForDate(Carbon::parse('2026-05-26')))->keyBy('time');

        $this->assertSame('buffer', $slots['09:30']['status']);
        $this->assertSame('booked', $slots['10:00']['status']);
        $this->assertSame('booked', $slots['11:30']['status']);
        $this->assertSame('buffer', $slots['12:00']['status']);
        $this->assertSame('available', $slots['09:00']['status']);
        $this->assertSame('available', $slots['12:30']['status']);
    }

    public function test_new_reservation_conflicts_with_buffer_zone(): void
    {
        Carbon::setTestNow('2026-05-26 08:00:00');

        $user = User::factory()->create();

        Reservation::query()->create([
            'user_id' => $user->id,
            'reserved_on' => '2026-05-26',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $service = app(ReservationSlotService::class);
        $date = Carbon::parse('2026-05-26');

        $this->assertTrue($service->hasConflict($date, '09:00', '09:30'));
        $this->assertFalse($service->hasConflict($date, '08:00', '08:30'));
    }
}
