# Autism Sanctuary — Public Website

Static **HTML/CSS** site (no build step) for **Autism Sanctuary**, a Virginia **501(c)(3)** and **DBHDS-licensed** provider. Content emphasizes **person-centered support**, **evidence-based practice**, **care farming**, **therapeutic horticulture**, **Green Care**, and **lifespan support** for autistic adults, adults on the spectrum, self-advocates, and individuals with intellectual and developmental disabilities (IDD).

Open `site/index.html` locally, or serve `site/` from any static host (GitHub Pages, Netlify, S3, etc.).

## Site map (primary pages)

| File | Purpose |
|------|---------|
| `site/index.html` | Homepage — model overview, services snapshot, workforce pipeline |
| `site/about.html` | Mission, values, history, **Care Farm Model & Philosophy** |
| `site/services.html` | **Our Services & Programs** — licensed lines, respite, trails/horticulture, community engagement |
| `site/flagship.html` | Virginia flagship blueprint |
| `site/expansion.html` | Community interest form (expansion) |
| `site/fellowship.html` | Carol Lynn Siemers Fellowship |
| `site/careers-volunteers.html` | DSP careers, volunteers, climate ambassadors, in-kind giving |
| `site/resources.html` | Waivers (FIS/BI/CL), CSA, accessibility & transit notes |
| `site/admissions.html` | Admissions FAQ + intake summary |
| `site/donate.html` | Philanthropy |
| `site/contact.html` | Contact form |
| `site/privacy.html` / `site/terms.html` | Legal |
| `site/apply.html`, `site/life.html`, `site/alumni.html` | Redirects to current pathways |

## Assets

- Styles: `site/assets/css/styles.css` (skip link, focus-visible, prose utilities, components)
- Images: `site/assets/images/` (referenced from CSS/HTML placeholders)
- Optional React + Postgres reference: `site/react-expansion/`

## Local preview

```bash
python -m http.server --directory site 8080
# http://localhost:8080
```

## Editorial note

Replace placeholder media blocks with approved photography from [autismsanctuary.org](https://www.autismsanctuary.org). Verify all clinical, respite, and transit statements with program leadership before publication.
