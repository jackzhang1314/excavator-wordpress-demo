# Design System — B2B WordPress Starter (historical theme draft)

> This file records an earlier visual proposal and is retained for historical comparison. The current implementation contract is [the Starter DESIGN document](../DESIGN.md), with actual tokens in [style.css](style.css). Do not use the values below as current defaults.

## 1. Design Tokens

### 1.1 Brand Colors (user-editable — change these 3 to re-skin the entire site)

| Token | CSS Variable | Default | Usage |
|---|---|---|---|
| Primary | `--brand-primary` | `#0B1526` | Headings, nav text, footer background |
| Accent | `--brand-accent` | `#1D5BDB` | Buttons, links, active nav, eyebrows |
| Surface | `--brand-surface` | `#F6F8FB` | Section backgrounds, card fills |

### 1.2 Derived Colors (auto-computed — never override)

| Token | Formula | Usage |
|---|---|---|
| `--ink` | `= brand-primary` | Body headings |
| `--body` | `mix(primary 78%, surface)` | Paragraph text |
| `--muted` | `mix(primary 55%, surface)` | Secondary text, captions |
| `--faint` | `mix(primary 34%, surface)` | Placeholder text |
| `--line` | `mix(primary 10%, white)` | Borders, dividers |
| `--line-strong` | `mix(primary 18%, white)` | Emphasized borders |
| `--accent-dark` | `mix(accent 80%, black)` | Button hover |
| `--accent-soft` | `mix(accent 8%, white)` | Accent backgrounds |
| `--accent-line` | `mix(accent 30%, white)` | Accent borders |
| `--ring` | `mix(accent 16%, transparent)` | Focus rings |

### 1.3 Semantic Colors

| Token | Value | Usage |
|---|---|---|
| `--ok` | `#0E8345` | Success text |
| `--ok-soft` | `#EAF7F0` | Success background |
| `--ok-line` | `#BFE5D0` | Success border |
| `--danger` | `#D92D20` | Error text |

### 1.4 Typography

| Token | Value | Usage |
|---|---|---|
| `--font` | `"InterVariable","Inter","Helvetica Neue",Arial,system-ui,sans-serif` | All text |
| H1 | `clamp(38px,4.6vw,62px)` / `1.12` / `700` / `-0.035em` | Page hero |
| H2 | `clamp(26px,3vw,38px)` / `1.15` / `700` / `-0.025em` | Section heading |
| H3 | `19px` / `1.2` / `600` / `-0.015em` | Card title |
| Body | `16.5px` / `1.7` / `400` | Paragraphs |
| Small | `14.5px` / `1.6` | Secondary text |
| Eyebrow | `12px` / `700` / `0.16em` / `uppercase` / accent | Labels above headings |

### 1.5 Spacing (4px base)

| Token | Value |
|---|---|
| space-1 | 4px |
| space-2 | 8px |
| space-3 | 16px |
| space-4 | 24px |
| space-5 | 32px |
| space-6 | 48px |
| section-y | 76px top |
| section-y-end | 8px bottom |
| hero-y | 88px top / 72px bottom |

### 1.6 Radius & Shadows

| Token | Value | Usage |
|---|---|---|
| `--radius` | 16px | Cards, hero photo, CTA band |
| `--radius-s` | 10px | Buttons, inputs |
| `--radius-xs` | 8px | Tags, badges |
| `--shadow-card` | subtle | Card default |
| `--shadow-lift` | prominent | Card hover, hero photo |
| `--shadow-header` | subtle | Sticky header |

### 1.7 Breakpoints

| Name | Width | Key changes |
|---|---|---|
| mobile | < 620px | 1-col grids, stacked hero, hidden topbar |
| tablet | < 980px | 2-col grids, hamburger nav |
| desktop | ≥ 980px | Full layout |

## 2. Components

WordPress templates must stay declarative. Reusable markup lives in `inc/components.php` and `parts/`; page templates compose those components and map CMS fields into component props. `header.php` owns the document shell and loads `parts/site-header.php`; `footer.php` owns document closure and loads `parts/site-footer.php`.

Current component contract:

| Component | Inputs | Owns |
|---|---|---|
| `component_page_head` | eyebrow, title, description, breadcrumbs | Inner-page H1 and breadcrumb placement |
| `component_section_heading` | eyebrow, title, description, link | Section label/heading hierarchy |
| `component_cta_band` | eyebrow, title, text, button | Primary conversion block |
| `component_stat_strip` | value/label items | Homepage proof strip |
| `parts/card` | current loop item + title_tag | Catalogue/solution/guide card |
| `parts/site-header` | WordPress settings + menu | Topbar and navigation |
| `parts/site-footer` | WordPress settings | Footer and copyright |

### Button

| Variant | Class | Style |
|---|---|---|
| Primary | `.button` | accent bg, white text, 8px radius |
| Ghost | `.ghost-button` | white bg, line border, ink text |

Hover: darken bg + translateY(-1px) + shadow

### Card

```html
<article class="card">
  <a class="card-media"><img></a>
  <div class="card-body">
    <h2 class="card-title"><a>Title</a></h2>
    <p class="excerpt">Excerpt</p>
    <span class="more">View →</span>
  </div>
</article>
```

- Border: 1px line / 12px radius
- Image: 4:3 aspect / cover
- Hover: lift + shadow + image scale

### Media placeholder

Starters use a neutral ratio placeholder, not a fake product photograph or illustrative icon. Each fallback states its intended asset size and ratio, for example `1200 × 900 · 4:3` for catalogue cards and `1600 × 1000 · 16:10` for homepage heroes. The frame is a light surface, dashed boundary and centered dimension label, so designers and users can replace it with a real asset without changing layout.

### Section Heading

```html
<div class="section-heading">
  <div><p class="eyebrow">Label</p><h2>Heading</h2></div>
  <a class="more">View all →</a>
</div>
```

### Spec Table

- Full width, 1px line border, 16px radius
- th: surface bg, muted text, 38% width
- td: white bg, ink text

### CTA Band

- Gradient(surface → accent-soft) background
- 1px line border / 16px radius
- 44px padding

## 3. Layout Patterns

### Page Hero (homepage)
- Grid: 1.05fr 0.95fr / gap 64px
- Left: eyebrow + h1 + prose + buttons
- Right: photo card (16px radius + shadow-lift)
- Stats bar below: 3-col grid, top/bottom border

### Page Head (inner pages)
- eyebrow + h1 + description
- border-bottom line
- padding: 72px 0 8px

### Breadcrumbs
- Below header, above content
- Font: 13.5px muted
- Separator: →

## 4. Do & Don't

### Do
- Use CSS variables for ALL colors
- Keep heading hierarchy (h1→h2→h3)
- Maintain 4.5:1 text contrast ratio
- Use `color-mix()` for derived colors
- Test at all 3 breakpoints

### Don't
- Don't hardcode color values
- Don't use inline styles
- Don't add external CSS frameworks
- Don't skip alt attributes
- Don't nest headings (h1 inside h2 etc.)
