<?php

namespace App\Http\Controllers\User;

use App\Helper\Cart;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Checkout\Session;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $carts = $request->carts;
        $products = $request->products;

        $mergedData = [];

        // Loop through the "carts" array and merge with "products" data
        foreach ($carts as $cartItem) {
            foreach ($products as $product) {
                if ($cartItem["product_id"] == $product["id"]) {
                    // Merge the cart item with product data
                    $mergedData[] = array_merge($cartItem, ["title" => $product["title"], 'price' => $product['price']]);
                }
            }
        }

        $paymentMethod = $request->input('payment_method', 'stripe'); // Default to stripe

        $checkout_session = null;
        if ($paymentMethod === 'stripe') {
            //stripe payment
            $stripe = new StripeClient(env('STRIPE_KEY'));
            $lineItems = [];
            foreach ($mergedData as $item) {
                $lineItems[] =
                    [
                        'price_data' => [
                            'currency' => 'myr',
                            'product_data' => [
                                'name' => $item['title'],
                            ],
                            'unit_amount' => (int) ($item['price'] * 100),
                        ],
                        'quantity' => $item['quantity'],
                    ];
            }

            $checkout_session = $stripe->checkout->sessions->create([
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel'),
            ]);
        }


        $newAddress = $request->address;
        if ($newAddress['address1'] != null) {
            $address = UserAddress::where('isMain', 1)->count();
            if ($address > 0) {
                $address = UserAddress::where('isMain', 1)->update(['isMain' => 0]);
            }
            $address = new UserAddress();
            $address->address1 = $newAddress['address1'];
            $address->state = $newAddress['state'];
            $address->zipcode = $newAddress['zipcode'];
            $address->city = $newAddress['city'];
            $address->country_code = $newAddress['country_code'];
            $address->type = $newAddress['type'];
            $address->user_id = Auth::user()->id;
            $address->save();
        }
        $mainAddress = $user->user_address()->where('isMain', 1)->first();
        if ($mainAddress) {
            $order = new Order();
            $order->status = 'unpaid';
            $order->total_price = $request->total;
            $order->session_id = $checkout_session ? $checkout_session->id : null;
            $order->created_by = $user->id;
            // If a main address with isMain = 1 exists, set its id as customer_address_id
            $order->user_address_id = $mainAddress->id;
            $order->save();
            $cartItems = CartItem::where(['user_id' => $user->id])->get();
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price,
                ]);
                $cartItem->delete();
                //remove cart items from cookie
                $cartItems = Cart::getCookieCartItems();
                foreach ($cartItems as $cartItem) {
                    unset($item);
                }
                array_splice($cartItems, 0, count($cartItems));
                Cart::setCookieCartItems($cartItems);
            }

            $paymentData = [
                'order_id' => $order->id,
                'amount' => $request->total,
                'status' => 'pending',
                'type' => $paymentMethod,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];
            Payment::create($paymentData);
        }

        if ($paymentMethod === 'stripe' && $checkout_session) {
            return Inertia::location($checkout_session->url);
        } else {
            // For COD, redirect to success/dashboard directly
            return redirect()->route('dashboard')->with('success', 'Order placed successfully. Please pay upon delivery.');
        }
    }

    public function success(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_KEY'));
        $sessionId = $request->get('session_id');
        try {
            $session = Session::retrieve($sessionId);
            if (!$session) {
                return redirect()->route('dashboard')->with('error', 'Unable to verify payment session.');
            }

            $order = Order::where('session_id', $session->id)->first();
            if (!$order) {
                return redirect()->route('dashboard')->with('error', 'Order not found for this payment session.');
            }

            // Stripe Checkout session payment_status: paid|unpaid|no_payment_required
            if (($session->payment_status ?? null) === 'paid') {
                if ($order->status === 'unpaid') {
                    $order->status = 'paid';
                    $order->save();
                }

                $payment = Payment::where('order_id', $order->id)->latest('id')->first();
                if ($payment && $payment->status !== 'succeeded') {
                    $payment->status = 'succeeded';
                    $payment->save();
                }

                return redirect()->route('dashboard')->with('success', 'Payment successful.');
            }

            return redirect()->route('dashboard')->with('error', 'Payment not completed yet. If you were charged, it will update shortly.');
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Unable to verify payment. Please check your orders.');
        }
    }

    public function cancel()
    {
        return redirect()->route('dashboard')->with('error', 'Payment was cancelled.');
    }
}
