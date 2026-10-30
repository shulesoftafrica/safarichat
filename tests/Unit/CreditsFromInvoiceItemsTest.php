<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\BillingWebhookController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A wallet top-up's payment.success carries the paid invoice items but no "credits" field, so the credits
 * bought have to be worked out from the items (one shilling = one credit).
 */
class CreditsFromInvoiceItemsTest extends TestCase
{
    private function credits(array $payload): int
    {
        $request = Request::create('/api/billing/webhook', 'POST', $payload);
        $method = new ReflectionMethod(BillingWebhookController::class, 'creditsFromInvoiceItems');
        $method->setAccessible(true);

        return $method->invoke(app(BillingWebhookController::class), $request);
    }

    public function test_a_tzs_credit_package_gives_one_credit_per_shilling(): void
    {
        $this->assertSame(5000, $this->credits([
            'invoice' => ['currency' => 'TZS', 'items' => [['price_plan_name' => 'AI Credit Package', 'total' => 5000]]],
        ]));
    }

    public function test_only_the_credit_items_are_counted(): void
    {
        $this->assertSame(2500, $this->credits([
            'invoice' => ['currency' => 'TZS', 'items' => [
                ['price_plan_name' => 'AI Credit Package', 'total' => 2500],
                ['price_plan_name' => 'Starter plan', 'total' => 69000],
            ]],
        ]));
    }

    public function test_other_currencies_are_not_guessed(): void
    {
        $this->assertSame(0, $this->credits([
            'invoice' => ['currency' => 'USD', 'items' => [['price_plan_name' => 'AI Credit Package', 'total' => 5]]],
        ]));
    }

    public function test_no_items_means_no_credits(): void
    {
        $this->assertSame(0, $this->credits(['invoice' => ['currency' => 'TZS', 'items' => []]]));
    }
}
