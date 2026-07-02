# VoxSign Landing Page v4 — Full Revamp Design Spec

**Status:** Author-decided under explicit user autonomy grant (2026-07-02:
"Dont ask for my input do everything on your own... Revamp everything we
have currently on the landing page i need new things only; just keep the
information that we have otherwise do your thing"). This spec documents the
decisions made in place of the normal interactive brainstorming Q&A, so the
reasoning is traceable. It is not a request for approval — it's a record.

**Stated assumption (narrower reading, not asked as a question):**
"revamp everything, new things only, keep the information" means new visual
treatment and a new avatar mechanism on top of v2's two-division information
architecture and all of v2/v3's copy — not a restructuring of what sections
exist or what they say. Section order, copy, team/partner/testimonial/
roadmap/contact facts are unchanged. If this reading is wrong, it's a fast
follow-up, not a rebuild.

## Goal

Three concrete complaints drove this: (1) the avatar demo "isn't coming out
the way I thought" — user wants an actual 3D avatar performing sign
language, not flat SVG hand shapes; (2) "I don't like the whole setup...
size of the logo" — the current PNG logo and its presentation are wrong;
(3) "well aligned when it comes to devices and web browsers" — a real
cross-viewport correctness pass, not just visual assertions.

## 1. 3D Avatar (replaces the v3 SVG hand-shape avatar-demo)

**Asset:** `thirg glb.glb` (repo root, gitignored) — glTF 2.0, Mixamo
skeleton, 68 nodes, two meshes (`Mesh`, `Mesh.001`) with flat PBR materials
(no textures): body `rgb(0.077,0.337,0.418)` ≈ teal, joints
`rgb(0.123,0.170,0.178)` ≈ dark navy. Only baked animation clip is a T-pose.

**Decision: procedural bone rotation in Three.js, not model-viewer.**
`<model-viewer>`'s public, documented API (`animationName`,
`availableAnimations`, `autoplay`) only plays baked-in glTF animation clips.
This GLB has none worth playing (T-pose only), and there's no GLB-authoring
tool in this environment (no Blender, no python3) to bake new clips
server-side. model-viewer does expose the underlying Three.js scene, but
only through unstable, undocumented internal properties — not something to
build on. Three.js's `SkinnedMesh.skeleton.bones` + `Object3D.quaternion`
is the stable, public, documented API for rotating individual bones at
runtime, and it's exactly what "basic sign language movement data... just
for showcasing" calls for: I author 2-3 simple keyframe poses myself
(shoulder/elbow/wrist/finger-curl rotations on the right arm, using the
exact bone names below) and tween between them client-side. No change to
the GLB file itself.

**Bones used** (confirmed via binary GLB inspection, exact glTF node
names): `mixamorig:RightArm`, `mixamorig:RightForeArm`,
`mixamorig:RightHand`, `mixamorig:RightHandIndex1/2/3`,
`mixamorig:RightHandMiddle1/2/3`, `mixamorig:RightHandRing1/2/3`,
`mixamorig:RightHandPinky1/2/3`, `mixamorig:RightHandThumb1/2/3`,
`mixamorig:Spine1`. Left arm and legs stay at bind pose (rest/idle) — only
the signing arm moves, keeping the poses legible rather than busy.

**Poses (2, matching the existing demo's 2 captions — `data-phrases="How
are you?,What is your name?"` in the current `avatar-demo.blade.php`, which
trace back to the user's own original example phrases):** each pose is a
hand-authored set of local quaternion rotations (Euler-to-quaternion at
build time) on the bones above:

1. **"How are you?"** — forearm raised in front of chest, hand near
   sternum, fingers relaxed-curved (partial curl on the three middle
   finger joints).
2. **"What is your name?"** — arm raised to shoulder height, open flat
   hand, slight wave rotation oscillation on `RightForeArm` (±8° over the
   pose's hold time).

Between poses: 600ms ease (`cubic-bezier(.4,0,.2,1)` equivalent via a JS
lerp/slerp easing function) hold each pose 3200ms, matching the existing
`setInterval(..., 3200)` caption-cycle timing already in the v3 partial's
JS — same two phrases, same interval, just driving 3D bone poses instead
of an SVG opacity crossfade.

**Rendering:** Three.js r16x via ESM CDN (`unpkg.com/three@<version>/...`)
with an import map in `layouts/marketing.blade.php`'s `<head>`, loaded only
on pages that render the avatar-demo partial (so the rest of the site pays
no Three.js cost) — use a `@stack('scripts')`/section include scoped to
that partial, `<script type="module">`. `GLTFLoader` from
`three/examples/jsm/loaders/GLTFLoader.js`. Materials recolored at load
time to brand colors (body → `--sign` teal, joints → `--voice` orange) via
`MeshStandardMaterial.color.set(...)` so the avatar reads as on-brand, not
as an arbitrary grey mannequin. Simple three-point lighting (ambient +
directional), `OrbitControls` NOT included (no free-drag — this is a fixed
showcase view, not an explorable model, per "just for showcasing").

**Fallback / accessibility (hard requirements carried over from v2/v3,
unchanged):**

- `prefers-reduced-motion: reduce` → load the model, render the bind pose
  once, do not run the pose-cycle animation loop. Captions still cycle
  (text only) on the same interval as before.
- WebGL unavailable / GLTFLoader fetch fails / Three.js CDN fails to load →
  catch and swap in the v3 SVG hand-shape illustrations that already exist
  in git history (`avatar-demo.blade.php` pre-v4) as a `<noscript>`-style
  JS fallback, not a blank space.
- "Concept preview" label and the non-linguistic-accuracy disclaimer text
  are unchanged, verbatim, from v2/v3 — this is still not a verified
  Ugandan Sign Language rendering, and the copy must keep saying so.
- Canvas has `aria-hidden="true"` (decorative relative to the caption text,
  which is the actual accessible content) plus a static `alt`-equivalent
  description in a visually-hidden element for screen readers.

## 2. SVG Logo

Current: raster PNG (`voxsign-logo.png`) at a size the user has flagged as
wrong. Replace with a hand-authored SVG (explicit permission granted to
create one — not a vector trace of the existing PNG, which isn't feasible
without dedicated tracing tooling; a new, on-brand mark).

**Design:** a geometric icon mark + wordmark, sized to sit comfortably in
the nav bar at a fixed height (`32px`, up from the current `28px` — part of
addressing "size of the logo") with correct aspect ratio so it never
stretches. Icon: two overlapping arcs (a sound-wave curve for "Vox" and a
raised-hand silhouette built from simple rounded rectangles/paths for
"Sign") rendered in `var(--grad)` (the existing orange→teal gradient
already used elsewhere on the page — reusing it here ties the mark to the
rest of the palette instead of introducing a fourth color). Wordmark
"VoxSign" in Clash Display, weight 600, sitting beside the icon.

**Files:** `resources/svg/voxsign-logo.svg` (source, for future editing) and
inlined directly in `marketing.blade.php`'s nav (`<svg>` inline, not
`<img>`) so the gradient can use the page's actual CSS custom properties
via `currentColor`/`<linearGradient>` stops referencing the same hex values
as `--voice`/`--sign`, and so it scales losslessly at any DPI — this also
fixes any raster-blur-at-2x-DPI contributor to "the logo looks wrong."
Footer keeps the existing text lockup (`VoxSign, Uganda`) — only the nav
mark changes; the footer never used the PNG logo.

I will render this in a browser (via the webapp-testing Playwright setup)
and visually check it before considering the task done — a hand-authored
SVG mark is exactly the kind of thing that looks fine as markup and wrong
on screen, so a screenshot check is part of the task, not optional polish.

## 3. Visual Revamp ("new things only, keep the information")

Scope this as a new art-direction pass on the existing shared layout and
section partials — same palette variables (`--ink`, `--paper`, `--surface`,
`--voice`, `--sign`, `--muted`, `--line`, `--grad`), same fonts (Clash
Display / Satoshi, just shipped in v3), same content — but a visibly
different treatment so it doesn't read as "the same page with a font
change," per "I need new things only":

- **Hero:** replace the current plain-background hero with a layered
  treatment — a large soft gradient blob/mesh (CSS `radial-gradient`
  layers, no images) behind the headline, and the headline's `.vx-flow`
  gradient-text span gets a subtle animated gradient shift
  (`background-position` keyframe, `prefers-reduced-motion`-gated) instead
  of a static gradient fill.
- **Section dividers:** replace the flat `border-bottom:1px solid
  var(--line)` between every section (currently uniform and slightly flat)
  with alternating subtle background tints on odd sections
  (`background:linear-gradient(180deg,var(--paper),#F5F3EC)` or similar
  near-paper tint) so sections read as distinct bands without needing hard
  lines everywhere.
- **Cards:** add a top accent bar (`2px`, `var(--grad)`) on `.vx-card` on
  hover (in addition to the existing v3 lift), and increase corner radius
  slightly (`16px` → `20px`) for a softer, more current feel.
- **Eyebrow labels:** replace plain uppercase text eyebrows
  (`.vx-eyebrow`) with a small pill/badge treatment — background tint +
  rounded-full border — instead of bare text, matching current SaaS
  landing-page conventions.
- **Numbers/stats visual weight** (how-it-works step numbers, any counters)
  get bumped to use the gradient text treatment (`.vx-flow`) for more
  visual anchoring per section.

None of this touches copy, section order, or the IA. Every partial file
under `resources/views/landing/partials/` keeps its current PHP data /
Blade `{{ }}` output bindings untouched — only surrounding markup/classes
for the new treatments change.

## 4. Cross-Device / Cross-Browser Verification

Local dev can't exercise these routes end-to-end (`ResolveTenant`
middleware requires Postgres, not available in this dev environment — a
standing, already-documented limitation). Verification instead runs
**after deploy, against the live site** (`https://voxsign.co.ug/`) using
the `webapp-testing` skill's Playwright setup, at four viewport widths:
375×812 (mobile), 768×1024 (tablet), 1024×768 (small laptop), 1440×900
(desktop). Checks: no horizontal overflow/scroll at any width, mobile nav
hamburger opens/closes and its menu is fully visible, no overlapping text,
the avatar canvas doesn't break layout or block scroll (touch/wheel events
pass through), logo renders crisply, hero gradient blob doesn't cause
overflow on narrow viewports. Findings get fixed and re-verified before
this work is considered done.

## Global Constraints

- No npm/Vite/webpack bundler introduced — Three.js loads via ESM CDN +
  import map, same "no build step" spirit as v3's vanilla approach, just a
  larger single-purpose library scoped to the one section that needs it.
- Palette custom properties (`--ink`, `--paper`, `--surface`, `--voice`,
  `--sign`, `--muted`, `--line`, `--grad`) are unchanged.
- Fonts (Clash Display / Satoshi) are unchanged, just shipped in v3.
- All existing copy/content (team roster, partners, testimonials, roadmap
  text, contact info, PearlEdu/Accessibility descriptions, ASR technical
  description, pricing-removed) is preserved verbatim.
- `prefers-reduced-motion` must be respected by every new animation
  (hero gradient shift, avatar pose-cycle, any new hover/transition).
- The avatar demo's "Concept preview" labeling and non-accuracy disclaimer
  are unchanged hard requirements — only the rendering mechanism changes.
- `*.glb` stays gitignored; the model ships to `public/models/` on the
  server via the existing deploy pipeline copying repo contents, but is
  never committed to git (already covered by the existing `.gitignore`
  entry — confirm the deploy step that gets it onto the server without a
  git commit, since cPanel Git Version Control only deploys tracked files).

## Open Technical Risk (flagged, not blocking)

cPanel's Git Version Control deploy only syncs tracked files — the
`.gitignore`'d GLB won't reach the server via the normal `git pull`-driven
deploy. This needs a one-time manual upload of `thirg glb.glb` to
`public/models/avatar.glb` on the server (via cPanel File Manager or SFTP),
separate from the git-based code deploy. This is called out explicitly in
the implementation plan as a manual deploy step, not something a subagent
can do.
