<?php

namespace Tests\Unit;

use App\Services\ReservationPricingService;
use PHPUnit\Framework\TestCase;

class ReservationPricingServiceTest extends TestCase
{
    public function test_two_hour_rental_is_ten_thousand_yen(): void
    {
        $service = new ReservationPricingService;

        $result = $service->calculate('10:00', '12:00');

        $this->assertSame(10000, $result['rental_fee']);
        $this->assertSame(0, $result['consumables_fee']);
        $this->assertSame(10000, $result['total_amount']);
    }

    public function test_extension_adds_two_thousand_five_hundred_yen_per_thirty_minutes(): void
    {
        $service = new ReservationPricingService;

        $result = $service->calculate('10:00', '12:30');

        $this->assertSame(12500, $result['rental_fee']);
    }

    public function test_consumables_and_tax_are_included_in_total(): void
    {
        $service = new ReservationPricingService;

        $result = $service->calculate('10:00', '12:00', ['charcoal', 'garbage']);

        $this->assertSame(10000, $result['rental_fee']);
        $this->assertSame(1000, $result['consumables_fee']);
        $this->assertSame(11000, $result['total_amount']);
        $this->assertSame(1000, $result['tax_amount']);
    }
}
