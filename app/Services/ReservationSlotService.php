<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ReservationSlotService
{
    public const OPEN_TIME = '09:00';

    public const CLOSE_TIME = '21:00';

    public const SLOT_MINUTES = 30;

    public const MIN_BOOKING_MINUTES = 120;

    public const BUFFER_MINUTES = 30;

    /**
     * @return list<string> Times in H:i format (09:00 … 20:30)
     */
    public function slotTimes(): array
    {
        $times = [];
        $cursor = Carbon::createFromTimeString(self::OPEN_TIME);
        $close = Carbon::createFromTimeString(self::CLOSE_TIME);

        while ($cursor->lt($close)) {
            $times[] = $cursor->format('H:i');
            $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return $times;
    }

    /**
     * @return list<array{time: string, status: string}>
     */
    public function slotsForDate(CarbonInterface $date): array
    {
        $states = array_fill_keys($this->slotTimes(), 'available');

        $reservations = Reservation::query()
            ->whereDate('reserved_on', $date->toDateString())
            ->get();

        foreach ($reservations as $reservation) {
            $this->applyReservationToStates($states, $reservation);
        }

        if ($date->isToday()) {
            $now = now();
            foreach ($this->slotTimes() as $time) {
                $slotStart = $date->copy()->setTimeFromTimeString($time);
                if ($slotStart->lt($now) && $states[$time] === 'available') {
                    $states[$time] = 'past';
                }
            }
        }

        return array_map(
            fn (string $time) => ['time' => $time, 'status' => $states[$time]],
            $this->slotTimes()
        );
    }

    public function isDateBookable(CarbonInterface $date): bool
    {
        $firstBookableDay = now()->copy()->addDay()->startOfDay();
        $lastBookableDay = now()->copy()->addMonth()->endOfMonth()->startOfDay();

        return $date->betweenIncluded($firstBookableDay, $lastBookableDay);
    }

    public function hasConflict(CarbonInterface $date, string $startTime, string $endTime): bool
    {
        $start = $date->copy()->setTimeFromTimeString($startTime);
        $end = $date->copy()->setTimeFromTimeString($endTime);

        $blockedStart = $start->copy()->subMinutes(self::BUFFER_MINUTES);
        $blockedEnd = $end->copy()->addMinutes(self::BUFFER_MINUTES);

        $existing = Reservation::query()
            ->whereDate('reserved_on', $date->toDateString())
            ->get();

        foreach ($existing as $reservation) {
            $existStart = $date->copy()->setTimeFromTimeString(
                $this->normalizeTime($reservation->start_time)
            );
            $existEnd = $date->copy()->setTimeFromTimeString(
                $this->normalizeTime($reservation->end_time)
            );
            $existBlockedStart = $existStart->copy()->subMinutes(self::BUFFER_MINUTES);
            $existBlockedEnd = $existEnd->copy()->addMinutes(self::BUFFER_MINUTES);

            if ($blockedStart->lt($existBlockedEnd) && $blockedEnd->gt($existBlockedStart)) {
                return true;
            }
        }

        return false;
    }

    public function rangeUsesOnlyAvailableSlots(CarbonInterface $date, string $startTime, string $endTime): bool
    {
        $slotsByTime = collect($this->slotsForDate($date))->keyBy('time');
        $cursor = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);

        while ($cursor->lt($end)) {
            $slot = $slotsByTime->get($cursor->format('H:i'));
            if (! $slot || $slot['status'] !== 'available') {
                return false;
            }
            $cursor->addMinutes(self::SLOT_MINUTES);
        }

        return true;
    }

    /**
     * @return list<array{label: string, weeks: list<list<array{date: string|null, day: int|null, selectable: bool, is_today: bool, is_selected: bool}>>}>
     */
    public function buildTwoMonthCalendars(): array
    {
        $calendars = [];
        $startMonth = now()->copy()->startOfMonth();

        for ($i = 0; $i < 2; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $calendars[] = $this->buildMonthCalendar($month);
        }

        return $calendars;
    }

    /**
     * @return array{label: string, weeks: list<list<array{date: string|null, day: int|null, selectable: bool, is_today: bool}>>}
     */
    private function buildMonthCalendar(CarbonInterface $month): array
    {
        $first = $month->copy()->startOfMonth();
        $last = $month->copy()->endOfMonth();
        $cursor = $first->copy()->startOfWeek(Carbon::MONDAY);
        $end = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];

        while ($cursor->lte($end)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $inMonth = $cursor->month === $month->month;
                $week[] = [
                    'date' => $inMonth ? $cursor->toDateString() : null,
                    'day' => $inMonth ? $cursor->day : null,
                    'selectable' => $inMonth && $this->isDateBookable($cursor),
                    'is_today' => $inMonth && $cursor->isToday(),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'label' => $month->format('Y年n月'),
            'weeks' => $weeks,
        ];
    }

    /**
     * @param  array<string, string>  $states
     */
    private function applyReservationToStates(array &$states, Reservation $reservation): void
    {
        $start = Carbon::createFromTimeString($this->normalizeTime($reservation->start_time));
        $end = Carbon::createFromTimeString($this->normalizeTime($reservation->end_time));

        $cursor = $start->copy();
        while ($cursor->lt($end)) {
            $time = $cursor->format('H:i');
            if (array_key_exists($time, $states)) {
                $states[$time] = 'booked';
            }
            $cursor->addMinutes(self::SLOT_MINUTES);
        }

        $bufferStart = $start->copy()->subMinutes(self::BUFFER_MINUTES);
        while ($bufferStart->lt($start)) {
            $time = $bufferStart->format('H:i');
            if (array_key_exists($time, $states) && $states[$time] !== 'booked') {
                $states[$time] = 'buffer';
            }
            $bufferStart->addMinutes(self::SLOT_MINUTES);
        }

        $bufferEnd = $end->copy();
        while ($bufferEnd->lt($end->copy()->addMinutes(self::BUFFER_MINUTES))) {
            $time = $bufferEnd->format('H:i');
            if (array_key_exists($time, $states) && $states[$time] !== 'booked') {
                $states[$time] = 'buffer';
            }
            $bufferEnd->addMinutes(self::SLOT_MINUTES);
        }
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof CarbonInterface) {
            return $time->format('H:i');
        }

        return Carbon::parse($time)->format('H:i');
    }
}
