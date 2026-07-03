# Landing Pages Redesign + Editable Pricing — Design

Date: 2026-07-03
Status: Approved (Option A)

## Goal

1. **voxsign.co.ug** — keep all current content (12 sections, all copy), bold professional visual redesign.
2. **pearledu.voxsign.co.ug** — bold redesign and expansion into a full SaaS landing page.
3. **Editable pricing** — PearlEdu pricing tiers stored in the database and managed from the existing Platform admin console.

## 1. voxsign.co.ug redesign

Views touched: `layouts/marketing.blade.php`, `landing/partials/*.blade.php`.
Content (headlines, body copy, team, partners, testimonials, contact details) is unchanged.

- Full-viewport dark ("ink") hero: animated gradient headline, layered radial glows and subtle grid texture, the 3D avatar retained with more stage presence. Copy identical.
- Alternating dark/light section bands for scroll rhythm (avatar demo and contact stay dark; more deliberate treatment elsewhere).
- Typography scale up for display headings; cards get gradient top borders, glass treatment on dark bands, refined hover and scroll-reveal motion (reduced-motion respected, as today).
- Nav becomes a floating pill bar; footer gains a closing CTA band.
- Fonts stay Google Sans + Satoshi. Brand colors stay `--voice:#FF6A3D`, `--sign:#12B3A6`.
- `tests/Feature/LandingPageTest.php` design-pinning assertions updated; all content assertions preserved (including: no "PearlEdu" and no pricing on the main site).

## 2. pearledu.voxsign.co.ug — full SaaS page

Views touched: `layouts/pearledu-landing.blade.php`, `landing/pearledu-home.blade.php`.

Section order:
1. Sticky nav with section links (Features, Pricing, FAQ) + Login CTA.
2. Bold hero with stylized dashboard mockup (pure HTML/CSS fake-browser frame).
3. Trust strip ("Built by VoxSign Technologies" + stats).
4. Feature deep-dives: attendance, grading, fees, communication — alternating text + CSS mockup rows. Current 4-card copy is preserved inside these.
5. Pricing — rendered from `pricing_plans` table (active plans, sorted). Null price shows "Contact us".
6. Testimonials.
7. FAQ accordion (`<details>/<summary>`, no JS dependency).
8. Final CTA band.
9. Onboarding form (existing fields, honeypot, and route unchanged; restyled).

Existing feature test assertions for the onboarding form, login links, and feature names remain satisfied.

## 3. Pricing plans — data + admin

- Migration `create_pricing_plans_table`: `id`, `name`, `tagline` (nullable), `price` (nullable unsigned integer — null renders "Contact us"), `currency` (default `UGX`), `billing_period` (default `per term`), `features` (json array of strings), `is_highlighted` (bool), `is_active` (bool, default true), `sort_order` (int), timestamps. Platform-level table — **not** tenant-scoped, no RLS (same class as `sms_settings`).
- Model `App\Models\PricingPlan` with `features` array cast and an `active()` scope.
- `PricingPlanSeeder`: three starter tiers (Starter / Standard / Enterprise), called from `DatabaseSeeder`.
- Controller `App\Http\Controllers\Platform\PricingPlanController`: `index`, `store`, `update`, `destroy`. Features edited as one-per-line textarea.
- Routes in `routes/platform.php` under existing `['web','auth','platform']` group: `platform.pricing.index/store/update/destroy`.
- View `platform/pricing/index.blade.php` on `layouts.app`: create card + per-plan inline edit forms with delete, matching the SMS console style.
- Sidebar: "Pricing" item added to the platform zone in `NavigationBuilder` (new `pricing` icon in the sidebar icon map).
- `PearlEduLandingController@index` passes `PricingPlan::active()->orderBy('sort_order')->get()` to the view.

## Testing

- `PearlEduLandingPageTest`: gains `RefreshDatabase` + seeding; new assertions for pricing (DB-driven), FAQ, and deep-dive sections; existing onboarding/login assertions kept.
- New `PricingPlanAdminTest`: platform operator can list/create/update/delete plans; non-platform users are denied; landing reflects changes.
- `LandingPageTest`: design assertions updated to the new CSS; content assertions untouched.

## Out of scope

- No changes to contact/onboard mail flows, routes, tenancy, or the 3D avatar loader.
- No drag-to-reorder UI for plans (numeric `sort_order` field instead).
