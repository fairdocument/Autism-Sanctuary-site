# WordPress site (autismsanctuary2)

Active Autism Sanctuary website: Divi + Gravity Forms on WPMU Unlimited.

**Staging:** https://autismsanctuary2-nimbusserver.tempurl.host/

## Server paths

| Item | Path |
|------|------|
| WP root | `/home/sites/autismsanctuary2/public_html` |
| SSH | `cursor@nimbusserver.tempurl.host` (`~/.ssh/cursor_wpmudev_ed25519`) |
| Live production (current) | `/home/sites/autismsanctuary/public_html` — do not overwrite |

## What’s on the site

- Brand CSS (cream/forest/gold + Cormorant Garamond / Source Sans 3)
- Pages: Home, About, People, Programs, Our farm, Resources, Careers, Donate, Thanks, Contact, Privacy, Terms, News
- Retired (draft + 301): Admissions → `/programs/#interest`, Fellowship → `/careers/`
- Gravity Forms: **Inquiry** (`?intent=` prepopulate), **Donate** (Stripe one-time + monthly)
- Gravity SMTP → SendGrid (`info@autismsanctuary.org`)
- Hustle Pro footer newsletter → SendGrid Marketing list **Web signups**
- SendGrid: Dynamic Templates (transactional) + Design Library / Single Sends (marketing)
- Primary + Footer menus
- News via Divi Blog module + Theme Builder single-post template

Brand CSS is loaded by mu-plugin `wp-content/mu-plugins/as-brand-css.php` from `wordpress-migration/custom.css`.

Google Analytics (GA4): plugin **Site Kit by Google** (`google-site-kit`) plus fallback mu-plugin `wp-content/mu-plugins/as-google-analytics.php` (`GT-P8Z4CWCX` → `G-Z2VYQCYE23`). The mu-plugin stops outputting once Site Kit Analytics is connected with `useSnippet`. Logged-in users are not tracked.

Site Kit OAuth must be finished in WP admin (cannot copy credentials from archive). After Sign in with Google, select existing Analytics property `G-Z2VYQCYE23` and Search Console `sc-domain:autismsanctuary.org`. Optional align script: `wp eval-file wordpress-migration/setup-site-kit.php`.

## Day-to-day editing

| Task | Where |
|------|--------|
| Page layouts | Pages → Edit with Divi |
| News index | Pages → News & updates → Edit with Divi |
| Single post chrome | Divi → Theme Builder → AS All Posts |
| Articles | Posts → Add New / Edit |
| Forms | Forms (Gravity Forms) |

## Scripts (optional re-run)

```bash
rsync -avz -e "ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes" \
  ./wordpress-migration/ \
  cursor@nimbusserver.tempurl.host:/home/sites/autismsanctuary2/public_html/wordpress-migration/

ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes cursor@nimbusserver.tempurl.host \
  'cd /home/sites/autismsanctuary2/public_html && wp eval-file wordpress-migration/setup-news.php'
```

Useful eval-files:

- `migrate.php` — initial page/media/menu seed
- `apply-olivia-updates.php` — Olivia 8/24 copy + IA (Admissions/Fellowship off, Looking Ahead, People bios)
- `setup-stripe-donate.php` — Donate form + Stripe feeds
- `setup-news.php` — Divi News page + Theme Builder posts
- `fix-news.php` — restore article HTML, featured images, thumbnail news list
- `convert-pages-to-divi5.php` — wrap marketing HTML as Divi 5 section layouts
- `convert-plain-html-to-divi.php` — convert builder-off plain HTML pages to Divi Code modules only
- `pilot-about-native-divi.php` — pilot native Divi Text/Image modules on /about/ (fixes copy typos)
- `convert-pages-native-divi5.php` — convert all other pages from Code HTML to native Divi 5 Text/Image
- `fill-excerpts.php` — rebuild post excerpts from Divi content
- `polish-design.php` — checklist/people markup tweaks
- `setup-gravity-smtp-sendgrid.php` — Gravity SMTP primary connector → SendGrid
- `setup-hustle-newsletter.php` — Hustle Pro embedded signup → Web signups list
- `create-sendgrid-templates.mjs` — create/refresh AS Alert + AS Newsletter dynamic templates
- `setup-sendgrid-marketing-drafts.mjs` — Design Library + Single Send draft (Trail Guide)
- `sendgrid-templates.json` / `sendgrid-marketing.json` — IDs (no API key)

```bash
SENDGRID_API_KEY='SG....' node wordpress-migration/create-sendgrid-templates.mjs
SENDGRID_API_KEY='SG....' node wordpress-migration/setup-sendgrid-marketing-drafts.mjs
wp eval-file wordpress-migration/setup-hustle-newsletter.php
```

## Before domain cutover

1. Confirm Stripe test/live mode with a small gift on `/donate/`
2. Intake still links to live `/intake-form/` until rebuilt in GF
3. Optional: Divi Theme Builder global header/footer
4. Replace image placeholders with approved photography
5. Point `autismsanctuary.org` only after QA sign-off

## Isolation

Never write to `/home/sites/autismsanctuary` (live production) from these scripts. EmDash (`autismsanctuary-new`) is retired and should not receive deploys.
