<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePaymentController extends Controller
{
    // Stage 1 keeps the old checkout as it was, with its routes fixed.
    // Stage 4 replaces it: the course's fee, a webhook that records the
    // payment, and the student's access opened only once Stripe confirms.
    public function checkout(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Simulation Payment',
                    ],
                    'unit_amount' => 1000, // Amount in cents (10 USD)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('student.simulation.stripe.success', ['simulation_id' => $request->route('simulation_id')]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('student.simulation.stripe.cancel', ['simulation_id' => $request->route('simulation_id')]),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        return view('student.simulation.payment.success');
    }

    public function cancel()
    {
        return view('student.simulation.payment.cancel');
    }
}
