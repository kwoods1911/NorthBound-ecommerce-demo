<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Payments\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
    ) {
    }

    public function show(): Response|RedirectResponse
    {
        $cart = $this->carts->currentCart();

        if (! $cart || $cart->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return Inertia::render('Checkout', [
            'summary' => $this->checkout->preview($cart),
            'stripeKey' => config('services.stripe.key'),
            'customer' => [
                'email' => auth()->user()?->email,
                'name' => auth()->user()?->name,
            ],
        ]);
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->carts->currentCart();

        if (! $cart || $cart->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        try {
            ['order' => $order, 'clientSecret' => $clientSecret] =
                $this->checkout->place($cart, $request->shipping());
        } catch (CheckoutException $e) {
            throw ValidationException::withMessages([
                'cart' => $e->issues ?: [$e->getMessage()],
            ]);
        } catch (ApiErrorException|RuntimeException $e) {
            report($e);

            throw ValidationException::withMessages([
                'payment' => 'We could not start the payment. Please try again shortly.',
            ]);
        }

        return response()->json([
            'clientSecret' => $clientSecret,
            'confirmationUrl' => URL::signedRoute('checkout.confirmation', $order),
        ]);
    }

    /** Stripe appends these to the return_url; they must not break the signature. */
    private const STRIPE_RETURN_PARAMS = ['payment_intent', 'payment_intent_client_secret', 'redirect_status'];

    public function confirmation(Request $request, Order $order, StripeGateway $stripe): Response
    {
        abort_unless(
            $request->hasValidSignatureWhileIgnoring(self::STRIPE_RETURN_PARAMS),
            403,
        );

        if ($order->status === OrderStatus::Pending && $request->filled('payment_intent')) {
            $intent = $stripe->retrievePaymentIntent($request->string('payment_intent')->toString());

            if ($intent->status === 'succeeded') {
                $this->checkout->finalizePaid($order, $intent->id);
                $order->refresh();
            }
        }

        return Inertia::render('Checkout/Confirmation', [
            'order' => (new OrderResource($order->load('items')))->resolve(),
        ]);
    }
}
