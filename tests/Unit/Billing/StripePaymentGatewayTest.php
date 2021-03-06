<?php

namespace Tests\Unit\Billing;

use App\Classes\Charge;
use Illuminate\Support\Arr;
use App\Classes\StripePaymentGateway;

/**
 * @group integration
 */
class StripePaymentGatewayTest extends PaymentGatewayContractTest
{
    protected function getPaymentGateway()
    {
        return new StripePaymentGateway(config('services.stripe.secret'));
    }

    protected function getValidTestToken()
    {
        return 'tok_visa';
    }

    public function newCharges($chargeCallback)
    {
        $latestCharge = $this->lastCharge();
        $chargeCallback();
        return $this->newChargesSince($latestCharge)->map(function ($stripeCharge) {
            return new Charge($stripeCharge['amount'], $stripeCharge['source']['last4']);
        });
    }

    private function lastCharge()
    {
        return Arr::first(\Stripe\Charge::all([
            'limit' => 1
        ], ['api_key' => config('services.stripe.secret')])['data']);
    }

    private function newChargesSince($charge = null)
    {
        $newCharges = \Stripe\Charge::all([
            'ending_before' => $charge ? $charge->id : null,
        ], ['api_key' => config('services.stripe.secret')])['data'];

        return collect($newCharges);
    }
}
