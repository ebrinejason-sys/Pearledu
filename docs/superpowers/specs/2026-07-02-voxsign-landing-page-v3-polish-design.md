# VoxSign Landing Page v3 — Polish & Premium Pass Design Spec

**Status:** Approved by user 2026-07-02. Applies on top of the v2 redesign
(`docs/superpowers/specs/2026-07-02-voxsign-landing-page-v2-design.md`),
which is already implemented, reviewed, and deploying. This spec covers
visual/interaction polish and a few targeted component redesigns — it does
not change v2's content model, information architecture, or section order.

## Goal

Raise the landing page's visual polish to a "premium" bar — consistent
spacing, a distinctive font pairing, a working mobile navigation menu, a
redesigned avatar-demo visual that avoids reading as a generic "AI avatar"
cliché, and smoother interaction/animation quality — all built in vanilla
CSS/JS with no new build tooling, since this is a server-rendered Blade app
with no JS bundler today and none is being introduced for this page.

## Explicitly Out of Scope

- No React, no Framer Motion, no npm/Vite build pipeline. The user
  considered this and chose vanilla CSS/JS to avoid adding a build step to
  an otherwise build-free Blade app.
- No changes to v2's content, section order, or the two-division
  information architecture.
- No literal import of 21st.dev component code (it's React-only) — "premium
  component" quality is the bar, achieved with hand-written CSS/JS.

## 1. Layout & Spacing

`.vx-section` currently uses a flat `padding: 72px 0` everywhere regardless
of viewport or section density. Replace with:

```css
.vx-section{padding:clamp(48px,8vw,88px) 0}
```

This tightens padding on mobile (`48px` floor) and scales up smoothly on
larger viewports (capping at `88px`, slightly more than the current `72px`
for extra breathing room) rather than jumping between fixed breakpoints.

Dense grid sections (Two Divisions, Features, PearlEdu's 4-card grid) get
additional bottom margin on their `.sec-head`-equivalent (`.vx-eyebrow`
+ `.vx-h2` + `.vx-lead` block) before the grid starts, since v2's inline
`style="margin-bottom:28px"` pattern is inconsistent across partials —
standardize this as a single utility class:

```css
.vx-sec-head{margin-bottom:clamp(28px,4vw,44px)}
```

Apply `.vx-sec-head` to the heading block in every section partial that has
one (divisions, pearledu, accessibility, how-it-works, features, team,
testimonials, roadmap), replacing their individual inline
`style="margin-bottom:..."` values.

## 2. Fonts

Replace the Google Fonts `Bricolage Grotesque` + `Atkinson Hyperlegible`
import with Fontshare's `Clash Display` (headings, via `--display`) and
`Satoshi` (body, via `--body`):

```html
<link rel="preconnect" href="https://api.fontshare.com">
<link href="https://api.fontshare.com/v2/css?f[]=clash-display@400,500,600,700&f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
```

```css
--display:'Clash Display',system-ui,sans-serif;
--body:'Satoshi',system-ui,sans-serif;
```

No other CSS custom properties change (palette stays the same: `--ink`,
`--paper`, `--surface`, `--voice`, `--sign`, `--muted`, `--line`, `--grad`).

## 3. Mobile Navigation

Add a hamburger menu that appears below `860px` (matching v2's existing
mobile breakpoint used elsewhere in the layout):

```css
.vx-nav-toggle{display:none;background:none;border:1.5px solid var(--line);border-radius:10px;padding:8px 10px;font-size:18px;cursor:pointer;margin-left:auto}
@media(max-width:860px){
  .vx-nav-links{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;
                background:var(--paper);border-bottom:1px solid var(--line);padding:16px 24px;gap:16px;margin:0}
  .vx-nav-links.open{display:flex}
  .vx-nav-cta{display:none}
  .vx-nav-toggle{display:block}
}
```

Markup: add a `<button class="vx-nav-toggle" aria-label="Menu" aria-expanded="false">` between `.vx-nav-links` and `.vx-nav-cta` in the nav, and a small inline script toggling `.open` on `.vx-nav-links` and updating `aria-expanded`. Clicking a link inside the open mobile menu closes it (so navigating doesn't leave the menu open on return).

## 4. Avatar Demo Redesign

Remove the current circle-head/stick-figure SVG entirely (it reads as a
generic "AI avatar" cliché — a blank circle for a head, with no other
detail, is exactly the kind of placeholder-feeling AI-generated visual this
project explicitly wants to avoid).

Replace with a set of 2–3 stylized SVG hand-shape illustrations — since
sign language communication is fundamentally about hand shapes, not a
face, this is both a more honest visual (avoids implying facial/identity
representation that isn't real) and sidesteps the "AI orb" look. Each hand
shape corresponds to one of the demo's phrases, with a CSS
opacity/transform crossfade between shapes as the caption cycles (replacing
the old arm-rotation animation). Same JS interval timing, same
`prefers-reduced-motion` handling (freeze on the first shape, keep caption
static), same "Concept preview" labeling and non-accuracy disclaimer text —
those requirements are unchanged from v2's spec, only the illustration
style changes.

## 5. Interaction Polish

- Scroll-reveal easing: replace the implicit default `ease` transition on
  `.vx-reveal` with an explicit smoother curve:
  `transition:opacity .7s cubic-bezier(.16,1,.3,1),transform .7s cubic-bezier(.16,1,.3,1)`
- Card hover-lift: add a subtle hover state to `.vx-card`:
  `transition:transform .2s ease,box-shadow .2s ease` and on hover
  `transform:translateY(-3px);box-shadow:0 12px 24px -16px rgba(11,16,32,.25)`
- Button polish: `.vx-btn`/`.vx-btn-ghost` already have a hover-lift from
  v2; keep as-is, no change needed.
- Grid stagger: sections using `.vx-grid` get a small stagger on their
  child cards' `.vx-reveal` transition-delay (e.g. `nth-child(n)` selectors
  adding `50ms * n` delay, capped at a reasonable max like `250ms`) so grid
  items animate in sequence rather than simultaneously.

## Global Constraints

- No new build tooling (no npm, no bundler, no React) — vanilla CSS/JS only.
- Palette (`--ink`, `--paper`, `--surface`, `--voice`, `--sign`, `--muted`,
  `--line`, `--grad`) is unchanged from v2 — only fonts change.
- v2's content, section order, and information architecture are unchanged —
  this is a visual/interaction pass only.
- `prefers-reduced-motion` must be respected by every new animation
  introduced here (scroll-reveal easing change, card hover, grid stagger,
  avatar-demo crossfade), consistent with v2's existing pattern.
- The avatar demo's "Concept preview" labeling and non-linguistic-accuracy
  disclaimer are unchanged hard requirements from v2 — only the
  illustration style changes, not the honesty/labeling requirements around it.
- Mobile nav must be keyboard/screen-reader reasonable: `aria-expanded`
  toggling on the button, `aria-label="Menu"`.

## Out of Scope / Future Work

- A real React/Framer Motion migration, if ever wanted, is a separate,
  larger architectural decision requiring its own brainstorming session —
  not bundled into this polish pass.
- Literal 21st.dev component imports (not possible without React).
