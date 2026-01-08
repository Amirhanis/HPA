<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');
        if (!$webhookSecret) {
            Log::warning('Stripe webhook called but STRIPE_WEBHOOK_SECRET is not set.');
            return response('Webhook not configured', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $type = $event->type ?? '';

            if (in_array($type, [
                'checkout.session.completed',
                'checkout.session.async_payment_succeeded',
                'checkout.session.async_payment_failed',
                'checkout.session.expired',
            ], true)) {
                $session = $event->data->object;
                $sessionId = $session->id ?? null;

                if (!$sessionId) {
                    return response('Missing session id', Response::HTTP_BAD_REQUEST);
                }

                $order = Order::where('session_id', $sessionId)->first();
                if (!$order) {
                    // Don’t fail webhook delivery if order is missing; log for diagnosis.
                    Log::warning('Stripe webhook: order not found for session', ['session_id' => $sessionId, 'type' => $type]);
                    return response('OK', Response::HTTP_OK);
                }

                $payment = Payment::where('order_id', $order->id)->latest('id')->first();

                // Stripe checkout session has payment_status: paid|unpaid|no_payment_required
                $paymentStatus = $session->payment_status ?? null;

                if ($type === 'checkout.session.completed' || $type === 'checkout.session.async_payment_succeeded') {
                    if ($paymentStatus === 'paid') {
                        if ($order->status === 'unpaid') {
                            $order->status = 'paid';
                            $order->save();
                        }
                        if ($payment && $payment->status !== 'succeeded') {
                            $payment->status = 'succeeded';
                            $payment->save();
                        }
                    }
                } elseif ($type === 'checkout.session.async_payment_failed') {
                    if ($order->status === 'unpaid') {
                        $order->status = 'payment_failed';
                        $order->save();
                    }
                    if ($payment && $payment->status !== 'failed') {
                        $payment->status = 'failed';
                        $payment->save();
                    }
                } elseif ($type === 'checkout.session.expired') {
                    if ($order->status === 'unpaid') {
                        $order->status = 'expired';
                        $order->save();
                    }
                    if ($payment && $payment->status === 'pending') {
                        $payment->status = 'expired';
                        $payment->save();
                    }
                }
            }

            return response('OK', Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handler error', ['message' => $e->getMessage()]);
            return response('Server error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
