<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class CheckoutController extends Controller
{
    private const ELITE_PRICE_MXN_CENTS = 49900;

    public function createSession(Request $request): JsonResponse
    {
        $user = $request->user();

        Stripe::setApiKey((string) config('services.stripe.secret', env('STRIPE_SECRET')));

        $successUrl = route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('checkout.cancel');

        $session = Session::create([
            'mode' => 'payment',
            'client_reference_id' => (string) $user->id,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'mxn',
                    'unit_amount' => self::ELITE_PRICE_MXN_CENTS,
                    'product_data' => [
                        'name' => 'Acceso Elite NexusEdu',
                        'description' => 'Desbloqueo total: Tutor IA ilimitado + Simulacros sin limites.',
                    ],
                ],
            ]],
            'metadata' => [
                'plan' => 'elite',
                'user_id' => (string) $user->id,
            ],
        ]);

        return response()->json([
            'url' => $session->url,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));

        try {
            if ($secret !== '') {
                $event = Webhook::constructEvent($payload, $signature, $secret);
            } else {
                $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            }
        } catch (UnexpectedValueException|SignatureVerificationException|\JsonException $exception) {
            Log::warning('Stripe webhook rejected', ['error' => $exception->getMessage()]);
            return response()->json(['ok' => false], 400);
        }

        $eventType = data_get($event, 'type');

        if ($eventType === 'checkout.session.completed') {
            $clientRef = (string) data_get($event, 'data.object.client_reference_id', '');

            if ($clientRef !== '' && ctype_digit($clientRef)) {
                $user = User::query()->find((int) $clientRef);

                if ($user) {
                    $user->forceFill(['is_premium' => true])->save();
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }

    public function success(): Response
    {
        return Inertia::render('Checkout/Success');
    }

    public function cancel(): Response
    {
        return Inertia::render('Checkout/Cancel');
    }
}
