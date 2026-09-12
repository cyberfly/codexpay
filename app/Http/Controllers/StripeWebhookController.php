<?php

namespace App\Http\Controllers;

use App\Actions\ProcessStripeWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Verify and process a Stripe webhook event.
     */
    public function store(Request $request, ProcessStripeWebhook $processStripeWebhook): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid Stripe webhook signature.', 400);
        }

        $processStripeWebhook->handle($event);

        return response()->noContent();
    }
}
