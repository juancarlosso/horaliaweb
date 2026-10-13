<?php

namespace Tests\Unit;

use App\Services\EmployeeSubscriptionPricing;
use Tests\TestCase;

class EmployeeSubscriptionPricingTest extends TestCase
{
    public function test_monthly_price_uses_blocks_of_up_to_two_hundred_registered_employees(): void
    {
        config(['constantes.precio_mensual_por_empresa_mxn' => 150]);
        $pricing = new EmployeeSubscriptionPricing();

        foreach ([
            0 => '150.00', 1 => '150.00', 199 => '150.00', 200 => '150.00',
            201 => '300.00', 399 => '300.00', 400 => '300.00', 401 => '450.00',
            600 => '450.00', 601 => '600.00',
        ] as $count => $expected) {
            $this->assertSame($expected, $pricing->priceForCount($count), "Unexpected price at {$count} employees.");
        }
    }
}
