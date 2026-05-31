<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationPricingService;
use App\Services\ReservationSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationSlotService $slotService,
        private ReservationPricingService $pricingService,
    ) {}

    public function create(): View
    {
        return view('reservations.create', [
            'calendars' => $this->slotService->buildTwoMonthCalendars(),
            'weekdays' => ['月', '火', '水', '木', '金', '土', '日'],
            'pricingConfig' => $this->pricingService->frontendConfig(),
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();

        if (! $this->slotService->isDateBookable($date)) {
            return response()->json([
                'message' => $date->isToday()
                    ? '当日分のご予約はお電話にて承ります。'
                    : '選択できない日付です。',
            ], 422);
        }

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $this->slotService->slotsForDate($date),
        ]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $pricing = $this->pricingService->calculate(
            $request->input('start_time'),
            $request->input('end_time'),
            $request->input('consumables', []),
        );

        $reservation = Reservation::query()->create([
            'user_id' => $request->user()->id,
            'reserved_on' => $request->input('reserved_on'),
            'start_time' => $request->input('start_time').':00',
            'end_time' => $request->input('end_time').':00',
            'consumables' => array_column($pricing['consumables'], 'id'),
            'rental_fee' => $pricing['rental_fee'],
            'consumables_fee' => $pricing['consumables_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'total_amount' => $pricing['total_amount'],
        ]);

        return redirect()
            ->route('reservations.show', $reservation)
            ->with('status', 'reservation-created');
    }

    public function show(Request $request, Reservation $reservation): View
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return view('reservations.show', [
            'reservation' => $reservation,
            'consumableItems' => $this->pricingService->selectedConsumables($reservation->consumables ?? []),
        ]);
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        $reservation->delete();

        return redirect()
            ->route('dashboard')
            ->with('status', 'reservation-cancelled');
    }
}
