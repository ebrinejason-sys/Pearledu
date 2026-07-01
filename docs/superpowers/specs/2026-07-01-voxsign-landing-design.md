# VoxSign Landing Page Redesign — Design Spec

## Context

The VoxSign apex domain (`voxsign.co.ug`) currently serves
`resources/views/landing/home.blade.php`, a bare scaffold that reuses the
internal PearlEdu app layout — it literally shows a "PearlEdu" topbar with a
theme pill on what should be VoxSign's own marketing page. It has no real
visual identity, no imagery, and only a one-paragraph pitch.

VoxSign already has a real marketing site at
`https://tusuubiravictor.wixsite.com/voxsign` with real copy, a real team,
real partners, and real (if early/anticipatory) testimonials. This spec
replaces the bare scaffold with a properly designed, standalone landing page
built from that real content — not invented copy.

## Goals

- Give VoxSign its own visual identity, separate from PearlEdu's internal
  app chrome and green/gold palette.
- Serve all four audiences identified during brainstorming (schools,
  educators, funders/government/NGOs, general public) without diluting the
  message.
- Use only real content: real team photos/roles, real partner logos, real
  testimonial quotes — kept in their honest, anticipatory framing since the
  product has not shipped yet ("I can't wait to try," not "I love using").
- Primary conversion action stays the existing contact form (decided during
  brainstorming) — not a "Download" button, since no app-store link is
  confirmed to exist yet.

## Visual Direction: Bold & Accessible

Approved during brainstorming (option B of three). High-contrast,
near-black background with yellow accent — chosen deliberately because the
audience includes Deaf/hard-of-hearing users, where strong contrast and
large, clear type are a functional requirement, not just a style choice.

CSS variables (new, scoped to the marketing layout only — does not touch
`config/themes.php`, which is PearlEdu's app-internal theming):

```
--vx-bg:        #0A0A0A
--vx-surface:   #141414
--vx-ink:       #FFFFFF
--vx-muted:     #B3B3B3
--vx-accent:    #FFD400
--vx-accent-ink:#0A0A0A
--vx-line:      #2A2A2A
--vx-radius:    4px
--vx-font:      system-ui, 'Segoe UI', sans-serif
```

Headings are heavy/blocky (font-weight 800–900), sharp corners (not
PearlEdu's rounded `--radius: 14px`), generous whitespace between sections.

## Media Assets (already downloaded)

Located at `public/images/voxsign/`, sourced directly from the real Wix
site's CDN:

| File | Content |
|---|---|
| `voxsign-logo.png` | VoxSign logo |
| `partner-unad.png` | Uganda National Association of the Deaf |
| `partner-kyu.png` | Kyambogo University, Faculty of Special Needs & Rehabilitation |
| `partner-youtube.webp` | YouTube (collaboration partner) |
| `partner-4.jpg` | TGN Systems ("Reshaping IT since 2003") |
| `team-victor.jpg` | Tusuubira Victor — CEO/Founder |
| `team-kamanzi.jpg` | Kamanzi Ahmed — Head of Marketing and Operations |
| `team-joan.jpg` | Muwanguzi Joan Najjingo — Finance and Sales Manager |
| `team-alex.jpg` | Muhumuza Alex — Head of Product Development |
| `team-sandra.jpg` | Naikambo Sandra — Sign Language Specialist and Consultant |
| `team-daniel.jpg` | Oyoka Daniel — Machine Learning Expert and Developer |

Note: Makerere University and Makerere Innovation & Incubation Centre are
named as partners/address on the Wix site but have no distinct downloaded
logo asset — they'll appear as text credits in the Partners section rather
than a logo tile.

## Page Sections (in order)

1. **Nav** — VoxSign logo + anchor links: How it works, Team, Partners,
   Pricing, Contact.

2. **Hero**
   - Eyebrow: "AI · UGANDAN SIGN LANGUAGE"
   - Headline: "Speak the Future. See It Signed."
   - Subhead: "Record, Transcribe, Collaborate. Effortlessly with VoxSign's
     AI avatar tool."
   - Primary CTA button: "Get in touch" → scrolls to `#contact`
   - Secondary link: "See how it works ↓" → scrolls to `#how-it-works`

3. **Mission** — the real problem statement: communication gaps between
   hearing instructors and Deaf/hard-of-hearing learners in Ugandan
   classrooms, and VoxSign's AI-avatar approach to closing that gap, framed
   for inclusive learning, accessibility, and collaboration.

4. **How it works** (`#how-it-works`)
   - Technical flow (3 steps): Speech captured live or recorded → AI
     Automatic Speech Recognition transcribes it → mapped to Ugandan Sign
     Language gestures, rendered as an animated avatar in real time.
   - End-user flow, framed as what's coming rather than shipped today:
     "Download → Create account → Tap Listen."

5. **Key features** (4 items, from the real site)
   - Automatic voice recognition that accommodates varied accents
   - Seamless collaboration with tools like YouTube and Zoom
   - Simple 3-step process
   - Multi-device accessibility

6. **Team** (`#team`) — 6 cards, photo + name + role, exactly as listed
   above under Media Assets.

7. **Partners** (`#partners`) — logo row: UNAD, Kyambogo University, YouTube,
   TGN Systems, plus text credits for Makerere University and Makerere
   Innovation & Incubation Centre.

8. **Testimonials** — the 3 real quotes, unedited in substance, kept in
   their anticipatory tense:
   - Naikambo Sandra (Student, Makerere University): "I can't wait to try
     VoxSign! The idea of having an AI avatar translate speech into Ugandan
     Sign Language sounds life-changing for students like me."
   - Birabwa Jane Lydia (Educator, Makerere University): "As an educator,
     I'm excited about VoxSign's potential to make classrooms more
     inclusive. If it works as described, it will truly revolutionize how
     we teach Deaf students."
   - Naikambo Sandra's Parent: "I'm really looking forward to VoxSign's
     launch. A tool that can help my child understand spoken words and
     communicate back would be a dream come true."

9. **Pricing** (`#pricing`) — table, exact figures from the real site:

   | Plan | Cost | Key Features |
   |---|---|---|
   | Free | UGX 0 | 3,000 words/day limit |
   | Premium | UGX 50,000/month | Unlimited translations, ad-free, collaboration tools |
   | Enterprise | UGX 50,000,000/year | Custom branding, dedicated support |
   | Government/NGOs | UGX 500,000,000/year | Full suite plus chatbot, analytics |

10. **Future roadmap** — brief section (good material for the
    funder/government audience): expansion across Africa to support
    multiple sign languages, integration with government and education
    systems, offline/wearable translation devices, a global multilingual
    sign-language AI model, community programs employing Deaf individuals.

11. **Contact** (`#contact`) — existing form (name, email, message,
    honeypot field, existing validation) kept exactly as-is, plus real
    contact details: phone `+256 770 680769`, email `voxsign3@gmail.com`,
    address "Makerere Innovation and Incubation Centre."

12. **Footer** — VoxSign brand line, contact repeat, link back to
    "PearlEdu — school management →". Deliberately **excludes** "Accessibility
    Statement" and "Privacy Policy" links present on the Wix site, since
    those pages don't exist in this codebase and a dead link is worse than
    no link. Out of scope for this spec; can be a follow-up.

## Technical Architecture

**New files:**
- `resources/views/layouts/marketing.blade.php` — standalone layout with
  VoxSign nav/footer and the Bold & Accessible inline `<style>` block
  (mirrors how `layouts/app.blade.php` inlines its own theme CSS — no new
  build tooling). Does not extend or reference the PearlEdu app layout.
- `resources/views/landing/partials/hero.blade.php`
- `resources/views/landing/partials/mission.blade.php`
- `resources/views/landing/partials/how-it-works.blade.php`
- `resources/views/landing/partials/features.blade.php`
- `resources/views/landing/partials/team.blade.php`
- `resources/views/landing/partials/partners.blade.php`
- `resources/views/landing/partials/testimonials.blade.php`
- `resources/views/landing/partials/pricing.blade.php`
- `resources/views/landing/partials/roadmap.blade.php`
- `resources/views/landing/partials/contact.blade.php`

**Modified files:**
- `resources/views/landing/home.blade.php` — becomes a thin shell:
  `@extends('layouts.marketing')` + `@include` for each partial in order.
- `app/Http/Controllers/LandingController.php` — `index()` builds and
  passes structured arrays (`$team`, `$partners`, `$testimonials`,
  `$pricingTiers`) to the view. This is static marketing content, not
  domain data — no new Eloquent models or migrations. `contact()` is
  unchanged.

**Unchanged:**
- Route wiring in `routes/web.php` (`is_landing` host-attribute check) —
  no change needed, still resolves to `LandingController@index`.
- Contact form validation, honeypot, and mail-sending logic.
- `config/themes.php` and the PearlEdu app's own theme system — VoxSign's
  palette is scoped to `layouts/marketing.blade.php` only and does not
  touch tenant-app theming.

## Testing / Verification

No new backend logic is introduced (content is static, contact flow is
unchanged), so no new automated tests are needed. Verification is manual:

1. `php artisan serve` locally (with `TENANCY_LANDING_HOSTS` including
   `localhost` per README) and visually confirm every section renders:
   nav, hero, mission, how-it-works, features, team (6 photos load),
   partners (4 logos load), testimonials, pricing table, roadmap, contact
   form, footer.
2. Submit the contact form and confirm existing validation/honeypot still
   works (no logic changed, but confirm the new layout doesn't break the
   form markup).
3. Resize to mobile width and confirm the team grid, partner row, and
   pricing table reflow without horizontal scroll or unreadable contrast.
4. Confirm the PearlEdu app itself (`pearledu.voxsign.co.ug` login flow,
   dashboard, etc.) is unaffected — this change only touches the marketing
   layout and landing view/controller, not `layouts/app.blade.php` or any
   tenant-facing view.

## Out of Scope

- Accessibility Statement / Privacy Policy pages (no live link to them yet).
- Any real "Download the app" link — no confirmed app-store URL exists.
- Video/animated avatar demo — no real footage exists yet (per earlier
  brainstorming answer); the "how it works" section is a text/diagram
  explainer, not a video embed.
- Changes to `config/themes.php` or PearlEdu's own theme system.
