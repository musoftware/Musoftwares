# Human UI Design & Anti-AI Slop Guidelines

## Core Rule
Eliminate generic AI-generated design clichés ("AI slop"), noisy card fatigue, buzzword copy overload, and synthetic templates. Build authentic, bespoke, human, responsive, accessible, and production-grade user interfaces.

---

## 1. Eliminate Card Fatigue & Visual Noise
- **Stop Wrapping Everything in Cards**: Do not force every single sentence, bullet point, or trivial feature into a bordered, shadowed card.
- **Maintain High Signal-to-Noise Ratio**: Minimize visual containers, glowing borders, and redundant wrapper boxes. Let typography, proximity, and whitespace structure the content.
- **No Pointless Bento Grids**: Do not force timelines, tables, sequential steps, or dense data into an asymmetric 4–6 box bento grid. Choose the layout that best fits the data (e.g., clean tables, linear lists, tabbed views, or editorial layouts).
- **No Fake Floating Glass**: Avoid decorative blurred glass cards (`backdrop-blur-md bg-white/10`) hovering aimlessly with no actionable data.

### 1.1 Strict Ban on "Russian Doll" Nested Cards (Cards Inside Cards)
- **The Antipattern**: Never wrap an entire section in a floating rounded card container (`rounded-[24px] bg-gray-50 border`), place cards inside it, and then wrap images/elements inside yet another layer of cards.
- **The Seamless Canvas Invariant**:
  - Sections must be open, full-width canvas spaces transitioning via generous vertical rhythm (`py-20 md:py-28`), background tint shifts, or subtle hairline divider rules (`border-t border-black/[0.06]`).
  - Never place cards inside cards. If content items need structure, use connected comparison matrices, column dividers (`divide-x` / `divide-y`), editorial whitespace, or crisp typographic hierarchy.
  - Screenshots and UI mockups must sit directly on the section canvas with a single clean border and natural shadow—never wrapped in padded container cards inside another card.

---

## 2. Eliminate Copywriting Clichés & Text Inflation ("The AI Voice")
- **No Buzzword Soup**: Strictly avoid empty corporate filler phrases:
  - *"In today's fast-paced digital world / landscape..."*
  - *"Unlock your potential / Supercharge your workflow..."*
  - *"Seamless integration / Cutting-edge solutions / Game-changing platform..."*
  - *"Not only does it do X, but it also empowers you to do Y."*
- **No Em Dash Addiction**: Avoid excessive em dashes (`—`) artificially connecting fragmented thoughts in titles and subheaders.
- **Write Concrete, Product-Specific Copy**: State exactly what the tool does in clear, human, and measurable terms with real domain terminology.

---

## 3. Eliminate The "Pill Badge" & Decorative Sparkle Spam
- **Stop Putting Pill Badges Above Every Heading**: (e.g., `✨ Introducing Version 2.0 ✨` or `🚀 AI-Powered Solution`).
- **Reserve Badges for Real Status**: Use badges strictly for meaningful state indicators (`Active`, `Pending`, `Failed`, `Verified`).
- **No Decorative Emojis/Sparkles**: Remove random sparkle (`✨`), lightning (`⚡`), and rocket (`🚀`) icons cluttering headers and buttons.

---

## 4. Eliminate Synthetic Visual Homogeneity & Color Traps
- **No Default Purple/Indigo Neon Mesh**: Avoid the generic AI gradient combo (`linear-gradient(135deg, #6366f1, #a855f7)`) on dark slate backgrounds with purple ambient blur orbs.
- **No Pure Pitch-Black Halation (`#000000`)**: Avoid `#000000` with pure `#ffffff` text, which causes eye fatigue and text vibration (halation). Use rich, layered dark surfaces (`#0c0d0e`, `#0f172a`, `#111827`).
- **Intentional Brand Palettes**: Use curated, domain-specific color systems (e.g., warm off-whites, deep navy, slate monochrome, terracotta, forest greens).
- **Distinct Typography**: Avoid unadjusted default Inter everywhere. Pair distinctive heading typefaces (Plus Jakarta Sans, Outfit, Cabinet Grotesk, Geist) with crisp, readable body typefaces.

---

## 5. Accessibility (a11y) & Readability Standards
- **Strict WCAG AA Contrast (4.5:1 Minimum)**: Never use unreadable light-grey on white or low-contrast dark-grey on dark backgrounds.
- **Accessible Interactive Elements**:
  - Every icon-only button must have an explicit `aria-label` or screen-reader title.
  - Never remove keyboard focus outlines (`outline-none`) without providing an explicit, high-contrast replacement focus state (`focus-visible:ring-2`).
- **Readable Font Sizes**: Minimum 14px for body content, with appropriate line-height (`1.5` to `1.75`).

---

## 6. Mobile Reality Over "Screenshot-Only" Layouts
- **Touch-Friendly Targets**: Ensure all clickable elements, inputs, and buttons meet minimum mobile touch target sizes (at least `44px × 44px`).
- **Resilient Breakpoints**: Test and ensure layouts gracefully stack on mobile screens without broken horizontal scroll, overlapping badges, or hidden buttons.
- **Mobile Input Usability**: Accommodate virtual keyboards and native form interactions.

---

## 7. Eliminate Fake Social Proof & Meaningless Metrics
- **No Generic Stat Counters**: Avoid floating cards with unverified claims (`"10,000+ Teams"`, `"99.9% Efficiency"`, `"5x Faster"`).
- **Authentic Social Proof**: Use verifiable customer quotes, real case studies, logo grids, or actual domain metrics.

---

## 8. Real Production-Grade UI Principles
1. **Typography Hierarchy**: Establish visual rhythm using scale, weight, and line height rather than nested borders and boxes.
2. **Intentional Whitespace**: Give elements breathing room and use proximity to group related items naturally.
3. **Subtle & Purposeful Motion**: Micro-interactions on buttons, inputs, and states (150ms–250ms ease-out). No dizzying scroll effects or perpetual bouncing.
4. **Functional Color Roles**: Define semantic tokens for background, surface, primary, muted, border, and statuses (Success, Warning, Destructive).

---

## 9. Eliminate Synthetic Telemetry & "Developer Gimmicks"
- **No Fake Pulsing Dots / Radar Lights**: Never place `animate-ping` green/blue radar dots in pill badges above hero headlines.
- **No Simulated Terminal / Fake Window Headers**: Never wrap hero product screenshots in fake macOS browser headers with colored window dots (`red/yellow/green`) and decorative mock paths (`project.core // telemetry`). Frame actual product interfaces cleanly with natural, subtle borders or realistic app viewports.
- **No Cluttered Hero Metric Rows**: Never dump arbitrary percentages (`99.98%`, `< 85ms latency`) directly below the hero CTA. If metrics are needed, integrate them naturally inside specific, verified case study narratives.
- **No Decorative Status Badges**: Remove floating `● Live` status dots from the corner of every image or card. Let the design breathe with calm typography.
- **No Repetitive Uppercase Kickers**: Avoid putting a small uppercase tracking label above every single heading. Group sections using whitespace and strong typographic scale.

---

## 10. Breaking AI Image Habits (Texture, Lighting & Composition)
When generating visual assets or using image prompts:
- **Break the "AI Gloss"**:
  - Ban "Unreal Engine" triggers: Never use `8k`, `hyper-realistic`, `masterpiece`, `volumetric lighting`, or `octane render` (triggers synthetic plastic look).
  - Force specific photographic mediums: Use terms like `candid smartphone photo`, `Kodak Portra 400`, `film grain`, `motion blur`, or `natural daylight`.
- **Fix Composition (No "Centered Stare")**:
  - Ban direct eye contact: Specify `candid`, `unaware of camera`, `looking away`, `profile shot`, or `over-the-shoulder`.
  - Force asymmetrical framing: Use `off-center`, `rule of thirds`, `extreme wide shot`, or `environmental portrait`.
  - Kill artificial bokeh: Avoid blurred background covers. Demand `deep depth of field`, `f/11`, or `sharp detailed background`.
- **Mitigate Hallucinations & Greebling**:
  - Obscure hands naturally when hands are not the subject (`hands in pockets`, `holding cup`, `arms crossed`).
  - Ban greebling (random over-detailing on hardware/interfaces). Force `minimalist`, `clean lines`, and `vast negative space`.
- **Handle Text in Images**:
  - Demand `no text`, `blank screen`, or `unbranded` to prevent pseudotext gibberish unless rendering exact explicit quotes.
- **Standard Visual Negative Blacklist**:
  - Exclude: `3d render, plastic, smooth skin, symmetrical, centered, direct eye contact, bokeh, artificial rim lighting, text, watermark, signature, over-saturated`.

---

## 11. Bypassing AI Web Design Clichés & Code Clichés

When generating UI mockups (Midjourney/Flux) or prompting UI code (Cursor, Claude, v0), models default to predictable aesthetics that look attractive in a portfolio mockup but fail in real-world UX. Follow these rules to eliminate them:

### 1. The "Dribbble Syndrome" (Form Over Function)
* **The AI Trope**: Visual polish over usability: tiny low-contrast gray text (`text-gray-400` on light background), excessive whitespace, and dysfunctional navigation menus that lack real-world routing.
* **The Counter-Rule**:
  - Enforce WCAG AA high-contrast compliance on all body text and form labels.
  - Never use light-gray text below contrast minimums (4.5:1 for normal text).
  - Ensure dense data layout and clear, functional navigation hierarchy.
  - Every button and link must have distinct `:hover` and `:focus-visible` keyboard states.

### 2. The Bento Box & Glassmorphism Defaults
* **The AI Trope**: Defaulting to an Apple-style bento grid of rounded cards filled with blurred backgrounds (`backdrop-blur-md bg-white/10`) and purple/teal neon gradients.
* **The Counter-Rule**:
  - Ban these defaults when structuring content: `--no bento box, rounded corners, glassmorphism, neon, purple gradients, dark mode`.
  - Force alternative architectural structures: Swiss typography layout, Brutalist web design, editorial print-inspired layout, or asymmetric functional grids.

### 3. The Generic 3D Hero Illustration
* **The AI Trope**: Two-column hero with bold text on the left and a floating 3D rendered asset (abstract blob, clay character, isometric desk, or rocket ship) on the right.
* **The Counter-Rule**:
  - Ban generic 3D illustrations.
  - Use typography-led design where the headline and typography carry the layout.
  - Use full-bleed documentary photography, authentic production software screenshots, or clean architectural diagrams instead of clip-art.

### 4. The Default Tailwind SaaS Look (Code Generators)
* **The AI Trope**: Code models defaulting to 2019 Tailwind clichés: `bg-white`, `shadow-sm`, `rounded-md`, and `text-indigo-600` primary buttons.
* **The Counter-Rule**:
  - Specify distinct design tokens, custom brand colors, and tactile personality.
  - Use crisp geometry: sharp corners (`rounded-none` or subtle `rounded-sm`), tactile solid borders (`border border-neutral-300`), and physical button styling instead of generic indigo pill buttons.

### 5. The "Perfect Data" Illusion
* **The AI Trope**: Populating mocks with uniform happy-path data: names that are exactly 12 characters, symmetrical cards, zero text wrapping, and zero error or empty states.
* **The Counter-Rule**:
  - Always design for messy data: test with very long names that wrap to 2 lines, international characters, and varying badge lengths.
  - Always specify empty states (when no records exist) and active field validation error states.

### 6. The Web Design Prompt Formula (For Image Mockups)
When generating UI mockup concepts in image generators, use this strict formula to bypass AI clichés:

```text
UI/UX design of a [Type of App/Website] for [Target Audience], [Specific Layout Style e.g., Editorial, Swiss Typography, Brutalist], focusing on [Key Feature e.g., dense data tables / typography / high-contrast readability]. Color palette: [Specific Colors e.g., monochrome black and white with safety orange accents]. Sharp edges, clear visual hierarchy, functional navigation. --no 3d renders, illustrations, glassmorphism, bento box, rounded corners, neon gradients, mobile app, isometric view, perspective skew
```



