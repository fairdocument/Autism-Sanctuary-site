# Autism Sanctuary — Public Website

**Active site:** WordPress + Divi on WPMU Unlimited  
**Staging:** https://autismsanctuary2-nimbusserver.tempurl.host/  
**Ops / scripts:** [`wordpress-migration/`](wordpress-migration/)

Autism Sanctuary is a Virginia **501(c)(3)** and **DBHDS-licensed** care farm in Western Albemarle. Content and design are edited in the WordPress admin (Divi Visual Builder, Gravity Forms, Theme Builder).

## Manage the site

| Task | Where |
|------|--------|
| Pages & homepage | Pages → Edit with Divi |
| News list layout | Pages → News & updates → Edit with Divi |
| Single news posts | Divi → Theme Builder → AS All Posts |
| Stories | Posts → Add New / Edit |
| Inquiry / donate forms | Forms (Gravity Forms) |
| Brand CSS | `wordpress-migration/custom.css` (mu-plugin loads it) |
| Google Analytics | Site Kit plugin + `wordpress-migration/as-google-analytics.php` fallback |

SSH: `cursor@nimbusserver.tempurl.host` · WP root: `/home/sites/autismsanctuary2/public_html`

## Archive (not in use)

| Path | Notes |
|------|--------|
| [`emdash-site/`](emdash-site/) | Retired EmDash/Astro experiment — do not deploy |
| [`site/`](site/) | Early static HTML prototype — reference only |

## Isolation

- Active staging: `/home/sites/autismsanctuary2/`
- Live production today: `/home/sites/autismsanctuary/` — do not overwrite from migration scripts
- Retired EmDash host: `/home/sites/autismsanctuary-new/` — no longer maintained

Point `autismsanctuary.org` to the Divi site only after QA sign-off.
