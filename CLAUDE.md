# CLAUDE.md

Project conventions and architecture decisions for this codebase. Follow these unless explicitly told otherwise in a prompt.

## Project overview

A demo ecommerce store selling travel gear and luggage (suitcases, carry-ons, weekender bags, packing cubes, travel accessories). Built as a portfolio piece to demonstrate architecture and AI integration skills. Visual/UX reference: awaytravel.com — clean, photography-led, generous whitespace, minimal chrome.

This is a demo, not a production store. Favour clarity and demonstrable good practice over exhaustive edge-case handling. Do not add features that were not asked for.

## Stack

- Laravel 12 (PHP 8.3+)
- MySQL
- Inertia.js + Vue 3 (Composition API, `<script setup>`)
- Tailwind CSS
- Stripe (test mode only)
- Filament for the admin panel only (runs on Livewire, mounted at `/admin`)
- Google Gemini API (`gemini-2.5-flash`) for the chatbot
- Deployed to a DigitalOcean droplet

Do **not** introduce a decoupled SPA, Vue Router, or an API-only backend. Routing stays in Laravel. Do not add state management libraries (Pinia/Vuex) — Inertia props plus local component state are sufficient at this scale.

Livewire exists in this project **only** because Filament depends on it. Do not use Livewire anywhere in the storefront. The two stacks stay strictly separated: Inertia+Vue for customer-facing pages, Filament/Livewire behind `/admin`.

## Architecture rules

**Controllers stay thin.** Controllers validate input, call a service, return an Inertia response. No business logic, no complex queries inline.

**Business logic lives in service classes** under `app/Services`. Examples: `CartService`, `CheckoutService`, `ProductSearchService`, `ChatService`. Services are constructor-injected, never resolved with `app()` inside methods.

**Query logic lives in Eloquent scopes**, not scattered across controllers. Collection pages are driven by scopes: `scopeNewArrivals`, `scopeBestSellers`, `scopeInCategory`, `scopeDiscounted`.

**One Collection page component, not four.** New Arrivals, Best Sellers, Accessories, and Deals & Bundles are the same Vue page component fed by different scopes. Never duplicate the collection view.

**Form Requests for validation.** No `$request->validate()` inline in controllers.

**Inertia responses pass explicit props.** Do not pass whole Eloquent models to the frontend — use API Resources or explicit arrays so the frontend contract is deliberate.

## Data model

- `products` — parent record: name, slug, description, base price, `cost_price`, category, `sales_count`, `stock_quantity`, `discount_price` (nullable)
- `product_variants` — belongs to product: colour, size, own image, optional price override
- **Stock is tracked at the product level only, not per variant.** Variants exist for display and selection. Do not build per-variant inventory.
- `categories`, `carts` / `cart_items`, `wishlists` (pivot: user ↔ product), `orders` / `order_items`
- `orders` carry a status: `pending` → `paid` → `shipped` → `delivered`, plus `cancelled`
- `chat_conversations` / `chat_messages` — session-scoped chatbot history
- `users` have an `is_admin` boolean (a full roles/permissions package is out of scope)
- `hero_images` — admin-managed: `image_path`, `headline`, `subheading`, `cta_label`, `cta_url`, `is_active` (boolean), `sort_order`. Only one row may be `is_active = true` at a time — activating one deactivates the rest, enforced in the model/service, not left to admin discipline.

**Historical accuracy rule:** `order_items` must snapshot `unit_price` **and** `unit_cost` at the time of purchase. Never join back to `products` to calculate past revenue or profit — editing a product price must not retroactively change reported figures.

Cart works for guests (session-based) and authenticated users. Merge the guest cart into the user cart on login.

## Authentication

Laravel Breeze (Inertia + Vue starter kit), restyled to match the site's design system — do not hand-roll auth from scratch.

- **Guest checkout is allowed.** An account is only required for wishlist and viewing order history. Do not gate checkout behind login.
- Customer login uses the same `web` guard and `users` table/provider as the Filament admin login — do not create a separate guard or a second users table. This is intentional, not a gap: `canAccessPanel()` already gates `/admin` on `is_admin`, so sharing the guard is safe.
- `MergeGuestCartOnLogin` already listens on the generic `Illuminate\Auth\Events\Login` event — it will start firing for customer logins automatically once Breeze routes exist. Do not duplicate this logic in a new listener.
- **Email verification: disabled.** Do not enforce `MustVerifyEmail`. `email_verified_at` stays unused.
- **Password reset: out of scope for this build.** Do not scaffold forgot-password/reset-password routes or UI, even though Breeze includes them by default — strip them out. No mail driver needs to be configured as a result.
- Before implementing, reconcile with what's already in the codebase: `users`, `password_reset_tokens` (leave unused, don't remove the table), `sessions` tables and `config/auth.php` are already stock Laravel and do not need to be touched.

## Design system

Photography-led, minimal chrome, quiet UI. Grounded in travel gear as a subject — canvas, brass hardware, navigation — not a generic "clean ecommerce" template. Avoid soft-shadow rounded SaaS cards, all-caps eyebrow labels, and numbered markers unless content is genuinely sequential (e.g. checkout steps).

**Color tokens** (define as CSS custom properties, use everywhere — no ad hoc hex values in components):

```css
--color-fog: #F4F3F0;      /* background */
--color-ink: #1B2027;      /* body text, headings */
--color-compass: #A6743D;  /* primary accent — CTAs, active states. Use sparingly */
--color-harbor: #2B3A55;   /* secondary accent — links, secondary buttons */
--color-stone: #C9C2B4;    /* borders, dividers, disabled states */
```

**Typography:** Fraunces (variable serif) for headings and hero type; Work Sans for body text and UI labels. Two families only, clearly distinct roles — do not introduce a third.

**Layout principles:**
- Large product photography is the hero, not decorative gradients or stock icons
- Flat, bordered cards (`--color-stone` border, no drop shadow) rather than rounded-shadow cards
- Generous whitespace over dense grids
- One deliberate motion moment per page (e.g. hero image reveal), not hover transitions on every element

## Homepage

Sections, in order:

1. **Hero** — single image pulled from the active `hero_images` row (admin-managed, see Admin panel and Data model). No auto-rotating carousel — one confident image, headline, and CTA. If no row is active, fall back to a sensible static default rather than an empty hero.
2. **Collection entry tiles** — visual links into New Arrivals / Best Sellers / Accessories / Deals
3. **Featured products grid** — real product cards (photo, name, price) pulled from the catalog, not placeholder content
4. **Trust strip** — short row: shipping, returns, warranty. Text only, no icon library dependency needed
5. **Editorial break** — one full-width lifestyle photo with brief brand copy, links to About. Static content, not admin-managed (out of scope, see below)
6. **Newsletter signup** — UI only, does not need to actually send/store anything for the demo
7. **Footer** — nav, social placeholders, legal link stubs

Avoid tracked-out all-caps eyebrow labels above headings (e.g. "TRAVEL GEAR CO" above the hero headline) — this is a generic AI-generated-page tell, not a brand element.

## Frontend conventions

- Persistent layout holds the header, cart badge, and chatbot panel so they survive navigation.
- Use `<Link prefetch>` on product grids and collection links.
- Filtering and pagination use **partial reloads** with `preserveState` and `preserveScroll` — never a full page visit.
- Every action that hits the server shows a loading state. Buttons disable while processing.
- Mobile-first. Test at 375px, 768px, 1280px.
- Product imagery is the focus — large images, lazy-loaded, correct aspect ratios reserved to avoid layout shift.

## Admin panel

Filament, mounted at `/admin`, gated to users with `is_admin = true`.

**Resources:**
- Products — full CRUD including variants, image upload, stock, price, cost price
- Orders — list and detail view, filterable by status, with status transitions. Orders are **not** deletable
- Users — read-only list
- Hero Images — full CRUD (image upload, headline, subheading, CTA label/URL, active toggle). The storefront homepage displays whichever row is currently active. Admins can prepare multiple hero images ahead of time and switch which is live without a deploy

**Dashboard widgets:**
- Total revenue, total profit (revenue − snapshotted cost), total orders, total units in inventory
- Recent orders (latest 10)
- New users (latest 10)
- Low-stock products (below threshold)
- Sales over time chart (last 30 days)

Aggregate calculations belong in a `ReportingService`, not inside widget classes — widgets call the service so the same numbers can be tested and reused.

Seed at least one admin user and enough realistic order history that the dashboard is not empty during a demo.

## AI chatbot

Provider-agnostic by design:

- Define an `AiChatProvider` interface in `app/Services/Ai`. Implement `GeminiChatProvider`. Bind in a service provider. **No Gemini-specific code outside that implementation.**
- Exactly **two** tools, no more: `searchProducts(query, category, maxPrice)` and `checkStock(productId)`. Both call existing services — the chatbot must not contain its own query logic.
- The bot answers questions about products, shipping, and returns. It cannot take actions (no add-to-cart, no order lookup).
- Tool results render as **product cards** in the chat UI, not plain text.
- Handle HTTP 429 gracefully with a friendly retry message. Never surface a raw API error.
- API keys in `.env` only. Never hardcoded, never committed, never exposed to the frontend.

## Testing

Pest feature tests for: cart operations, checkout flow, collection scopes, chatbot tool dispatch, `ReportingService` calculations, and admin route authorization (non-admins must be denied). Run the suite after implementing a feature and fix failures before reporting done.

## Planning expectations

Enter Plan Mode for: schema changes, checkout/payment work, authentication, the chatbot tool loop, the admin panel, and deployment configuration. Plans must name affected files, call out risks, and state how the change will be verified. Small, single-file changes do not need a plan.

## Out of scope

Product reviews, customer-facing order tracking, email notifications, password reset, email verification, multi-currency, coupon codes, per-variant inventory, refunds, roles/permissions packages, CSV export, SSR, auto-rotating hero carousels, admin management of homepage content beyond hero images (editorial section and newsletter copy stay static). Do not build these. Ask before adding anything not listed in this file.