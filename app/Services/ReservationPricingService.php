<?php

namespace App\Services;

use Carbon\Carbon;

class ReservationPricingService
{
    public const BASE_MINUTES = 120;

    public const BASE_PRICE = 10000;

    public const EXTENSION_PRICE_PER_30MIN = 2500;

    public const TAX_RATE = 10;

    /**
     * @return list<array{id: string, name: string, price: int}>
     */
    public static function consumables(): array
    {
        return [
            ['id' => 'charcoal', 'name' => 'BBQ用炭 2kg', 'price' => 500],
            ['id' => 'fire_starter', 'name' => '着火剤', 'price' => 100],
            ['id' => 'ice', 'name' => '氷 1kg', 'price' => 300],
            ['id' => 'paper_plates', 'name' => '紙皿セット', 'price' => 200],
            ['id' => 'paper_cups', 'name' => '紙コップセット', 'price' => 100],
            ['id' => 'chopsticks', 'name' => '割り箸セット', 'price' => 100],
            ['id' => 'garbage', 'name' => 'ゴミ回収', 'price' => 500],
        ];
    }

    /**
     * @return list<string>
     */
    public static function consumableIds(): array
    {
        return array_column(self::consumables(), 'id');
    }

    public function rentalFee(int $durationMinutes): int
    {
        $fee = self::BASE_PRICE;

        if ($durationMinutes > self::BASE_MINUTES) {
            $extraSlots = ($durationMinutes - self::BASE_MINUTES) / ReservationSlotService::SLOT_MINUTES;
            $fee += (int) $extraSlots * self::EXTENSION_PRICE_PER_30MIN;
        }

        return $fee;
    }

    /**
     * @param  list<string>  $consumableIds
     * @return array{
     *     duration_minutes: int,
     *     rental_fee: int,
     *     consumables_fee: int,
     *     subtotal: int,
     *     tax_amount: int,
     *     total_amount: int,
     *     consumables: list<array{id: string, name: string, price: int}>
     * }
     */
    public function calculate(string $startTime, string $endTime, array $consumableIds = []): array
    {
        $start = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);
        $durationMinutes = $start->diffInMinutes($end);

        $selectedConsumables = $this->selectedConsumables($consumableIds);
        $consumablesFee = array_sum(array_column($selectedConsumables, 'price'));
        $rentalFee = $this->rentalFee($durationMinutes);
        $subtotal = $rentalFee + $consumablesFee;
        $taxAmount = (int) floor($subtotal * self::TAX_RATE / (100 + self::TAX_RATE));

        return [
            'duration_minutes' => $durationMinutes,
            'rental_fee' => $rentalFee,
            'consumables_fee' => $consumablesFee,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal,
            'consumables' => $selectedConsumables,
        ];
    }

    /**
     * @param  list<string>  $consumableIds
     * @return list<array{id: string, name: string, price: int}>
     */
    public function selectedConsumables(array $consumableIds): array
    {
        $allowed = array_flip(self::consumableIds());

        return array_values(array_filter(
            self::consumables(),
            fn (array $item): bool => isset($allowed[$item['id']]) && in_array($item['id'], $consumableIds, true)
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function frontendConfig(): array
    {
        return [
            'basePrice' => self::BASE_PRICE,
            'extensionPrice' => self::EXTENSION_PRICE_PER_30MIN,
            'minBookingMinutes' => self::BASE_MINUTES,
            'slotMinutes' => ReservationSlotService::SLOT_MINUTES,
            'taxRate' => self::TAX_RATE,
            'consumables' => self::consumables(),
        ];
    }
}
