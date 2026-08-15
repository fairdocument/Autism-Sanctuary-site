# Autism Sanctuary — Public Website

**Primary build (WordPress / Divi):** staging at https://autismsanctuary2-nimbusserver.tempurl.host/ — migration package in [`wordpress-migration/`](wordpress-migration/).

**Prior EmDash (Astro) theme:** [`emdash-site/`](emdash-site/), staged at https://autismsanctuary-new-nimbusserver.tempurl.host/ — see [`emdash-site/README.md`](emdash-site/README.md).

**Reference static prototype:** `site/` remains a static **HTML/CSS** draft for **Autism Sanctuary**, a Virginia **501(c)(3)** and **DBHDS-licensed** **care farm** in **Western Albemarle**.

Open `site/index.html` locally, or serve `site/` from any static host (GitHub Pages, Netlify, S3, etc.).

## Site map (primary pages)

| File | Purpose |
|------|---------|
| `site/index.html` | Homepage — model overview, services snapshot, workforce pipeline |
| `site/about.html` | Mission, values, history, **Care Farm Model & Philosophy** |
| `site/services.html` | **Programs & licensed services** — day, residential, respite, trails/horticulture, community engagement |
| `site/flagship.html` | **Our farm** (Pea Ridge Road / Western Albemarle) |
| `site/expansion.html` | **Future locations** — community interest (not admissions) |
| `site/fellowship.html` | Carol Lynn Siemers Fellowship |
| `site/careers-volunteers.html` | DSP careers, volunteers, climate ambassadors, in-kind giving |
| `site/resources.html` | Waivers (FIS/BI/CL), accessibility & transit notes (no CSA coordination) |
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

Replace placeholder media blocks with approved photography from [autismsanctuary.org](https://www.autismsanctuary.org). Verify respite, waiver, and transit statements with program leadership before publication.
