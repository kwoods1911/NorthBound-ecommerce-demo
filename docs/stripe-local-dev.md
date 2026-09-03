# Stripe test-mode checkout — local setup

The checkout flow uses the **Stripe Payment Element** (embedded) in test mode.

## 1. Keys

From the Stripe dashboard → Developers → API keys (test mode), set in `.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

## 2. Webhook forwarding

Payment success is confirmed by the `payment_intent.succeeded` webhook (it marks
the order paid, draws down stock, bumps `sales_count`, empties the cart). Forward
events to the app with the Stripe CLI:

```
stripe listen --forward-to localhost:8000/stripe/webhook
```

Copy the printed `whsec_...` secret into `.env`:

```
STRIPE_WEBHOOK_SECRET=whsec_...
```

If `stripe listen` is **not** running, the confirmation page still finalises the
order via a return-redirect fallback (it re-checks the PaymentIntent with Stripe
on the way back). The webhook is the authoritative path; the fallback is a safety
net.

## 3. Test cards

| Card | Result |
|---|---|
| `4242 4242 4242 4242` | succeeds |
| `4000 0000 0000 0002` | declined |

Any future expiry, any CVC, any postal code.

## 4. Flow

`/cart` → `/checkout` (shipping form + Payment Element) → pay → redirected to a
signed `/checkout/confirmation/{order_number}` showing the paid order.

Notes:
- Stock is decremented on **payment success**, not when checkout starts.
- Guest checkout is allowed; the confirmation URL is signed so guests can view
  their order without an account.
- `POST /checkout` returns JSON (not an Inertia response) because the client
  needs the `clientSecret` to drive `stripe.confirmPayment` — the one place the
  storefront steps outside Inertia.
