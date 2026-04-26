# Autism Sanctuary Clinical Fellowship — Website

A complete, recruiter-grade website for the **Autism Sanctuary Clinical Fellowship**: a 13-month, cohort-based gap-year program for recent graduates pursuing medicine, PA programs, and clinical psychology. The program is modeled on Project Horseshoe Farm’s Community Health Fellowship (experiential learning, mentorship, reflection, community engagement) and adapted to Autism Sanctuary’s clinical staffing architecture for the DD/ID population in Central Virginia.

This repository ships **static HTML/CSS** with no build step. Open `site/index.html` in any browser, or drop the `site/` directory onto any static host (Netlify, GitHub Pages, S3+CloudFront, Vercel).

---

## Site Structure

```
site/
├── index.html         Homepage — Hook, Mission, Why Choose Us
├── about.html         About the Fellowship — Structure & Curriculum
├── life.html          Life as a Fellow — Culture & Well-being
├── alumni.html        Alumni & Career Impact
├── apply.html         Admissions, Timeline, Application Form, FAQ
└── assets/
    ├── css/styles.css Brand stylesheet (palette, typography, components)
    ├── js/            (reserved — no JS dependencies today)
    └── images/        Drop site photography here (see "Image Slots")
```

### Page-by-page outline

#### 1. Homepage (`index.html`) — *The Hook & Mission*
- **Hero** — full-bleed photograph of the Enchanted Forest / Edgefield with a 65% forest-green gradient overlay and CTAs.
- **Stats strip** — direct clinical hours, months of service, fellow:client ratio, 85-acre Edgefield property.
- **Mission** — Autism Sanctuary’s dedication to the DD/ID population and the fellow’s role as the backbone of that mission.
- **Why Choose Us** — 6-card grid: clinical hours, crisis management, prestigious credential, mentorship, cohort living, up-or-out path.
- **Testimonial / Video** — fellow video placeholder + pull-quote.
- **Year at a Glance** — Service / Clinical / Leadership / Reflection.
- **Final CTA** — Two cohorts/year, Apply Now.

#### 2. About the Fellowship (`about.html`) — *Structure & Curriculum*
- **The Up-or-Out Model** — finite 1-to-2 year commitment; ~80% to elite programs, top 10–20% promoted to Senior Fellow / Lead DSP.
- **Staggered Cohorts** — Summer (July) and Winter (January) intakes; rolling handoff for continuity of care.
- **30-60-90 Onboarding Framework** — visual timeline of foundations → supervised practice → independent lead.
- **Shadow & Fade** signature protocol — diagram of overlapping handoff curves.
- **Consistency Mapping** signature protocol — anonymized client-routine document framework.
- **Curriculum Modules I–VI** — DD/ID care, behavioral & crisis practice, health systems, clinical communication, leadership/operations, reflective practice.

#### 3. Life as a Fellow (`life.html`) — *Culture & Well-being*
- **A Week in the Life** — hour breakdown across direct service, seminars, leadership, reading, and protected MTO.
- **Teamwork & Leadership** — cohort housing, program leadership, supervision of interns/volunteers, mentorship triads, reflective practice, community engagement.
- **Health & Burnout Prevention** — Mandatory Time Off (MTO), structured micro-breaks, post-event clinical debriefs, psychological safety, fellowship-funded counseling, physical resilience via the property.
- **Senior fellow testimonial.**

#### 4. Alumni & Career Impact (`alumni.html`)
- **Outcomes stats strip** — placeholder figures to replace with verified data.
- **Where they matriculated** — logo-grid format (replace with school crests/wordmarks).
- **Alumni spotlights** — 3-card portrait + pull-quote layout.
- **The Network** — mentorship match, application support, research pipeline, boomerang hires, lifetime affiliation.
- **Boomerang alumni section** — alumni who returned to Edgefield as attending clinicians, board members, advisors.

#### 5. Apply (`apply.html`) — *Admissions & Requirements*
- **Target audience** — pre-med, pre-PA, clinical psychology; explicit university partnership messaging (UVA et al.).
- **What we look for** — service track record, academic rigor, emotional steadiness, coachability, team orientation, communication, resilience, clear ‘why’.
- **Timeline** — clear application windows for Summer and Winter cohorts.
- **Interview process** — 3 rounds (current fellow → clinical lead → Executive Director).
- **Application form** — name, contact, university, grad year, cohort, track, “why” free-text.
- **FAQ** — paid?, prior clinical experience?, MCAT/GRE during year?, second-year path.

---

## Brand & Design System

Stylesheet: `site/assets/css/styles.css`. The palette draws from Autism Sanctuary’s nature-forward identity (the Enchanted Forest, Edgefield, the agricultural hub).

| Token | Hex | Usage |
|---|---|---|
| Sanctuary Green | `#2F5D43` | Primary brand color |
| Forest Deep | `#1E3D2C` | Headings, dark sections, footer |
| Meadow Light | `#A7C4A0` | Soft accent backgrounds, pills |
| Stone Cream | `#F5F1E8` | Page background |
| Edgefield Gold | `#C9A646` | CTAs, eyebrow accents |
| Charcoal Ink | `#1B1B1B` | Body text |

**Typography:** Cormorant Garamond (display serif) + Inter (UI sans) via Google Fonts. The serif evokes the prestige of an established fellowship; the sans keeps the program copy crisp and modern.

**Tone:** prestigious, mission-driven, empathetic, plain-spoken. Calibrated to read credibly to a UVA pre-med while still feeling earned by a non-profit serving the DD/ID community.

---

## Image Slots — How to drop in Autism Sanctuary photography

The site uses styled placeholder blocks (`.media-placeholder`) anywhere a real photograph or graphic should appear. Each placeholder includes a `<small>` caption describing the intended image. The assets to source from `https://www.autismsanctuary.org`:

1. **Logo** — replace the temporary `.brand-mark` round badge in every page header/footer with the official Autism Sanctuary leaf/tree logo (SVG preferred).
2. **Hero image (`index.html`)** — full-bleed photo of the Enchanted Forest trail, Edgefield porch at golden hour, or pasture. Drop into `site/assets/images/hero-forest.jpg` (already referenced in CSS).
3. **Mission image (`index.html`)** — Enchanted Forest trail photograph.
4. **Cohort photo (`life.html`)** — fellows or volunteers with clients on the property.
5. **Boomerang alumni photo (`alumni.html`)** — alumni clinician on site, ideally with a current fellow in frame.
6. **Alumni portraits (`alumni.html`)** — 3 square portraits.
7. **Schools logo grid (`alumni.html`)** — replace the placeholder grid with grayscale school wordmarks.
8. **Diagrams (`about.html`)** — the Career Arc, Shadow & Fade overlap curve, and Consistency Map mockup are explicitly called out in `<!-- LAYOUT NOTE -->` comments and should be produced by your designer.
9. **Sample weekly calendar (`life.html`)** — color-coded weekly grid graphic.
10. **Testimonial video (`index.html`)** — 60–90 second fellow testimonial; poster frame on the Edgefield trail.

Every placeholder is annotated in the HTML with an `<!-- LAYOUT NOTE -->` comment describing the intended asset.

---

## Local preview

No dependencies, no build step. From the repo root:

```bash
python3 -m http.server --directory site 8080
# then open http://localhost:8080
```

Or simply double-click `site/index.html`.

---

## Editorial notes

- All numerical claims marked with an asterisk on `alumni.html` are illustrative placeholders. Replace with verified outcome data once collected.
- The placeholder testimonials on `index.html`, `life.html`, and `alumni.html` are fictional examples designed to model the right voice — replace with real, attributed quotes (with consent) before launch.
- The application form is a no-backend HTML stub. Wire it to your CRM (HubSpot, Airtable, Salesforce) or a Formspree/Netlify Forms endpoint before launch.
- Address, phone, email, and Instagram handle are pulled from `autismsanctuary.org` and partner directories.
