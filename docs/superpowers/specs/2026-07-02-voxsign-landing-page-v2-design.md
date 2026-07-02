# VoxSign Landing Page v2 — Design Spec

**Status:** Approved by user 2026-07-02. Supersedes the visual system from
`docs/superpowers/specs/2026-07-01-voxsign-landing-design.md` (the "Bold &
Accessible" dark palette). Content facts from that prior spec (team roles,
partners, contact details, testimonials) still apply except where this spec
explicitly changes them (team roster, pricing removal).

## Goal

Redesign `voxsign.co.ug`'s landing page from the current dark ("Bold &
Accessible") theme to a light-mode design that correctly represents VoxSign
as a platform with two divisions — PearlEdu (school management, for
institutions) and VoxSign Accessibility (assistive tools for Deaf/hard-of-
hearing and speech-impaired users) — remove pricing, add a stylized
sign-language avatar demo, and fix the contact form's email flow so
submitters get a confirmation and the founder receives inquiries directly.

## Visual System

Base palette and fonts adopted from the user-provided reference mockup:

```css
--ink:#0B1020;      /* body text, footer/band background */
--paper:#FBFAF7;    /* page background */
--surface:#FFFFFF;  /* card background */
--voice:#FF6A3D;    /* orange — "speech" accent */
--sign:#12B3A6;      /* teal — "sign language" accent */
--muted:#5D6473;
--line:#E7E4DC;
--grad: linear-gradient(100deg, var(--voice), var(--sign));
--display: 'Bricolage Grotesque', system-ui, sans-serif;
--body: 'Atkinson Hyperlegible', system-ui, sans-serif;
```

Page is light-mode overall. 1–2 dark navy (`--ink`) "band" sections are
permitted for contrast (Services-equivalent section, footer) — this is a
contrast technique within a light page, not a site-wide dark theme; the
prior fully-black theme is retired.

Animations: scroll-reveal on section entry (IntersectionObserver, respects
`prefers-reduced-motion`), hover micro-interactions on buttons/cards, and a
continuous partners-strip marquee (see below). Interaction quality should
match the polish level of modern component libraries (e.g. 21st.dev) —
implemented in vanilla CSS/JS since this is a server-rendered Blade page
with no React/bundler pipeline; do not introduce one for this page.

No "AI design giveaway" patterns: no generic purple/blue gradient blobs, no
symmetric three-icon-grid-with-no-hierarchy sections, no default
Inter-everywhere typography, no stock-photo-style hero art. The
speech-wave→hand-icon "signature" visual and the asymmetric hero grid from
the reference mockup are the kind of specific, intentional choices to keep.

## Information Architecture

Single scrolling page, in this order:

1. **Nav** — brand, links (Institutions / Accessibility / Team / Contact), "Talk to us" CTA. Sticky, blurred background on scroll (per reference mockup).
2. **Hero** — platform-level intro to VoxSign as an inclusion-and-education technology company (not product-specific). Speech→sign animated "signature" visual (existing wave/arrow/hand SVG treatment from the reference, reused as-is).
3. **Partners strip** — real logos (existing assets), rendered as an **infinite auto-scrolling marquee**: logos scroll continuously leftward in a seamless loop (duplicate the logo set once for a seamless wrap), grayscale by default, animation pauses and the hovered logo turns full-color on `:hover`. Respect `prefers-reduced-motion` (freeze the scroll, keep hover color-reveal).
4. **Two Divisions** — short section introducing the split: two cards, "PearlEdu" and "VoxSign Accessibility", each with a one-line description and an anchor-link ("Learn more ↓") to its own section below.
5. **PearlEdu section** — what it does for institutions (school management: attendance, grading, fees, communications, based on this repo's actual scope — no invented feature claims beyond what's in this codebase's domain). Framed for a school administrator reader.
6. **VoxSign Accessibility section** — intro paragraph, then two product sub-cards:
   - **Ugandan Sign Language app** — existing content (AI avatar converts speech to USL in real time).
   - **Assistive Speech Recognition** — new copy (see Content below), explained in plain language for a general reader, not technical jargon.
7. **Sign-language avatar demo** — see dedicated spec below.
8. **How it works** — adapted from reference mockup's 3-step layout, generalized where it currently only describes the sign-language pipeline (keep the 3-step flow for that product specifically, since "how it works" doesn't cleanly generalize to ASR).
9. **Features** — adapted from reference mockup, covering both Accessibility products (drop any feature bullet that only makes sense for one specific implementation not yet built).
10. **Team** — see roster change below.
11. **Partners (detail)/Testimonials** — existing real content, restyled to the new palette.
12. **Roadmap** — existing real content, restyled.
13. **Contact** — new email flow (see Backend below), restyled form.
14. **Footer** — dark band, existing real links/info.

**Removed entirely:** pricing section and its nav link. No pricing anywhere on the page.

## Content Changes

**Team roster** (`LandingController::team()`):
- Remove: Oyoka Daniel (Machine Learning Expert and Developer)
- Add: Aaron Marshall Taremwa — role "Product Development Expert"
- Add: Ebrine Tushabe — role "Product Development Expert"
- Both new members have no photo yet — use an initials-fallback avatar (same pattern as the reference mockup's `.avatar.noimg` treatment: colored panel showing the person's initials instead of a broken image).

**Assistive Speech Recognition copy** (new, factual, from the user directly — do not embellish beyond this):
> VoxSign's speech recognition tool helps people with speech impairments be
> understood. It's built by training OpenAI's Whisper speech-recognition
> model on non-standard speech patterns, so it can accurately recognize what
> someone is saying even when their speech doesn't follow typical patterns.

Keep this in plain language on the page; the Whisper/technical detail can
appear as a brief supporting line, not the headline claim.

**PearlEdu copy**: describe as a school management platform for
institutions — attendance, grading, fees, and communication tools — based
on this repository's actual domain scope. No specific feature should be
claimed that doesn't correspond to work that exists or is planned in this
codebase.

## Sign-Language Avatar Demo

A stylized 2D animated avatar (SVG/CSS, not video/photo), cycling through
2–3 basic phrases (e.g. "How are you?", "What is your name?") with the
phrase captioned on screen during its animation. Explicitly labeled on the
page (e.g. a small "Concept preview" tag near the section heading) as an
illustrative preview of the product concept — not a claim of linguistically
verified Ugandan Sign Language, since no verified reference movement data
is available yet. Movement style: simple articulated shapes (arms/hands)
performing a distinct motion pattern per phrase, looping, with
`prefers-reduced-motion` fallback showing a static labeled frame per phrase
instead of animating.

## Contact Form Backend Changes

Current state: `LandingController::contact()` sends one `Mail::raw()` to
`config('mail.contact_inbox')` (currently `hello@voxsign.co.ug`, per the
`config/mail.php` fix from 2026-07-02).

New behavior:
1. **`CONTACT_INBOX`** changes to `tusuubiravictor@gmail.com` (replaces
   `hello@voxsign.co.ug` — this was explicitly requested; not additive).
2. **New Mailable `App\Mail\ContactFormReceived`** — replaces the current
   `Mail::raw()` admin notification. Sent to `config('mail.contact_inbox')`,
   contains the submitter's name, email, and message; reply-to set to the
   submitter's email (as today).
3. **New Mailable `App\Mail\ContactFormConfirmation`** — sent to the
   submitter's own email address. Branded "On behalf of VoxSign" sender
   name, short message confirming the inquiry was received and that VoxSign
   will be in touch shortly. Uses `config('mail.from.address')` as the
   envelope from-address (already configured via `MAIL_FROM_ADDRESS`/
   `MAIL_FROM_NAME`), not the submitter's own address (a marketing/legit
   deliverability requirement — you cannot send mail "from" an address you
   don't control).
4. The existing honeypot (`website` field must be empty) and validation
   rules (`name`, `email`, `message`) are unchanged.
5. `back()->with('status', ...)` behavior on success is unchanged — the
   flash message shown on the page after submission.

## Global Constraints

- No invented marketing copy beyond what's provided in this spec or already
  verified in `project_voxsign_marketing_content` memory — if a new content
  gap appears during implementation, flag it rather than inventing specifics.
- Light-mode is the site-wide theme; dark sections are contrast bands, not a
  toggle or a site-wide dark theme.
- No pricing content anywhere on the page (nav, sections, or CTAs).
- Avatar demo must not claim linguistic accuracy it doesn't have — the
  "Concept preview" labeling is a hard requirement, not optional styling.
- Contact form must send exactly two emails per successful submission (admin
  notification + submitter confirmation) and must not regress the existing
  honeypot/validation/reply-to behavior.
- Reuse existing real assets (`public/images/voxsign/*`) for team/partners
  where photos exist; use initials-fallback avatars only for the two new
  team members who have no photo yet.
- `prefers-reduced-motion` must be respected for the partners marquee and
  the avatar demo animation, consistent with the existing `.reveal`
  scroll-animation pattern already in this codebase.

## Out of Scope

- Real USL-verified avatar movement data (pending input from Naikambo Sandra
  or another sign-language reference source — a future iteration, not this one).
- Any React/bundler tooling changes to this page.
- Photos for Aaron Marshall Taremwa and Ebrine Tushabe (to be added later
  when available).
- Changes to `pearledu.voxsign.co.ug` (the platform app itself) — this spec
  covers only the marketing landing page.
