# Maison Anversa — Development Plan Prompt (Cursor Agent, Plan Mode)

## Context

We are building the production website for **Maison Anversa**, a European heritage luxury sports & lifestyle brand (Antwerp-based). The project starter kit is already set up on **Laravel 13 + Inertia 3 + React 19**, with Spatie Permission, multi-language support, and several other features already implemented.

We now need to build out the full Maison Anversa site on top of this existing codebase, using the provided prototype (`index.html`, `i18n.js`) as the exact design/UX/animation reference, and the Developer Briefing PDF as the technical spec.

Reference files provided:
- `docs/reference/Maison_Anversa_Developer_Briefing.pdf` — full technical specification (design system, page structure, cinematic effects, i18n approach, e-commerce/CMS requirements, acceptance criteria)
- `docs/reference/index.html` — fully functional HTML/CSS/JS prototype (~5,300 lines), contains all 14 pages, styling, animations, and scripts
- `docs/reference/i18n.js` — i18n engine with 500+ approved NL/EN/FR translations

**Do not skip reading these reference files. They are the source of truth for design, copy, and behavior — this is a build spec, not a design exploration.**

---

## Task — Work through the following steps in order. Do not start writing new feature code until Steps 1–4 are complete and documented.

### Step 1 — Full Codebase Analysis
Analyze the entire existing project before touching anything:
- List all existing features, modules, and routes currently implemented
- List all installed packages/dependencies (composer.json + package.json), noting their purpose
- Document the current database schema (all migrations, tables, relationships)
- Map out the current file/folder structure (backend: app/, routes/, database/; frontend: resources/js/, components, pages, layouts)
- Identify existing auth setup, Spatie Permission/roles/teams configuration, and multi-language implementation already in place
- Flag anything that conflicts with or duplicates what Maison Anversa will need (e.g. existing theme system, existing i18n approach, existing page/routing conventions)

Output this as a written summary/audit before proceeding — do not assume, verify by reading actual files.

### Step 2 — Install & Configure Missing Packages
Based on the prototype's cinematic effects (film grain, vignette, custom cursor, magnetic buttons, page transitions, scroll reveals, parallax, marquee, preloader animation) and the briefing's mention of possible 3D/immersive elements:
- Install `three`, `@react-three/fiber`, `@react-three/drei` (and any other required Three.js ecosystem packages) if not already present
- Install any animation libraries needed to replicate the effects faithfully (e.g. GSAP, Framer Motion — evaluate what's already available vs what's missing)
- Verify pnpm is used (not npm) for all installs
- Confirm all new packages build cleanly with the existing Vite/Laravel setup — no version conflicts
- Do NOT install packages that aren't actually needed for effects specified in the briefing — avoid bloat

### Step 3 — Read Prototype & i18n Thoroughly
- Read `index.html` in full — understand every page's structure, every cinematic effect's implementation (CSS + JS), the slideshow/immersive intro logic, and all interactive behaviors
- Read `i18n.js` in full — understand the translation data structure, the TreeWalker approach used in the prototype, and extract the full NL/EN/FR translation dataset to be used as the source data for the production i18n system
- Cross-reference against the Developer Briefing PDF for anything not obvious from the code alone (e.g. acceptance criteria, effect specifications, page purposes)
- Produce a page-by-page and effect-by-effect breakdown before implementation begins

### Step 4 — Design System, Theme & Typography Configuration
Configure the design system to exactly match the briefing (Section 3) and prototype:
- Colors (exact hex values, no deviation): `--choc #291c18`, `--choc2 #352722`, `--choc3 #41332d`, `--gold #8d705a`, `--gold2 #745a48`, `--cream #f3ebe3`, `--cream2 #e8e0d5`, `--sand #b8a898`, `--stone #8a7d72`
- Fonts: Baskervville (headings/serif) + Montserrat Light 300 (body/UI) via Google Fonts — no other typefaces
- Type scale exactly as specified (hero, section title, subtitle, eyebrow, body, small/metadata)
- Spacing/layout: 1280px max width, section padding per breakpoint, minimal border-radius

**Critical — Light mode only:**
- The site must render in **light mode only**, always — there is no dark mode variant for this brand
- If the existing starter kit has a theme system (light/dark/system toggle), it must be overridden or disabled for this project so that:
  - The site ignores OS/browser `prefers-color-scheme` entirely
  - Any existing theme toggle component is either removed from this site's UI or hardcoded to force light mode
  - If a user's system is set to dark mode, or if any theme-switching logic exists in the starter kit, it must not affect Maison Anversa — it should always resolve to light mode
- Confirm this is enforced at the root layout/provider level, not just per-page

### Step 5 — Build Pages to Match Prototype Exactly
Build all 14 pages (Home, Maison/House, Product, Story, Founding Circle, Dressing Room, Journal, Community, Club Corner, Contact, Privacy, Terms, Shipping, Care) plus the immersive slideshow intro:
- Match the prototype pixel-for-pixel in layout, spacing, and visual structure
- Preserve all 10 cinematic effects exactly as specified in the briefing (Section 5), including mobile variants, and respect `prefers-reduced-motion: reduce`
- Use the approved NL/EN/FR copy from `i18n.js` as-is — do not rewrite or improvise content
- Build with Laravel 13 + Inertia 3 + React 19 conventions already established in the starter kit (component structure, routing patterns, existing Spatie Permission/Team setup where relevant for the Founding Circle member area)
- Mobile-first, fully responsive per the breakpoints in the briefing (375px / 768px / 1024px / 1440px+)
- Use dummy/placeholder images matching the prototype's current images for now — do not block on real photography

---

## Output Expected from Plan Mode

Before writing implementation code, produce a written plan covering:
1. Codebase audit summary (Step 1 findings)
2. List of packages to install with justification (Step 2)
3. Prototype/i18n breakdown — page list + effect list with implementation notes (Step 3)
4. Design system + light-mode-lock implementation approach (Step 4)
5. Page-by-page build order and component architecture (Step 5)

Wait for confirmation on this plan before beginning full implementation.
