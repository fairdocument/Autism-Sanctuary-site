# WordPress migration (autismsanctuary2)

Migrates the EmDash redesign onto the Divi WordPress site
`https://autismsanctuary2-nimbusserver.tempurl.host/`.

## Server paths

| Item | Path |
|------|------|
| WP root | `/home/sites/autismsanctuary2/public_html` |
| SSH | `cursor@nimbusserver.tempurl.host` (`~/.ssh/cursor_wpmudev_ed25519`) |
| Live WP (news source) | `/home/sites/autismsanctuary/public_html` — do not overwrite |
| EmDash staging | `/home/sites/autismsanctuary-new/public_html` — media source |

## What the migration creates

- Site title, tagline, permalinks, front page = Home, posts page = News
- Brand CSS (cream/forest/gold + Cormorant Garamond / Source Sans 3)
- Media library assets (farm photos + hero video + logo)
- Pages: Home, About, People, Programs, Our farm, Admissions, Resources, Careers, Fellowship, Donate, Donate/Thanks, Contact, Privacy, Terms, News
- Gravity Forms: **Inquiry** (intent prepopulate via `?intent=`), **Donate Inquiry**
- Primary + Footer menus
- News posts imported from live `autismsanctuary.org`

## Re-run

```bash
rsync -avz -e "ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes" \
  ./wordpress-migration/ \
  cursor@nimbusserver.tempurl.host:/home/sites/autismsanctuary2/public_html/wordpress-migration/

ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes cursor@nimbusserver.tempurl.host \
  'cd /home/sites/autismsanctuary2/public_html && wp eval-file wordpress-migration/migrate.php'
```

News import (after exporting JSON into `news-export/`):

```bash
wp eval-file wordpress-migration/import-news.php
```

Brand CSS is also loaded by mu-plugin `wp-content/mu-plugins/as-brand-css.php`.

## News (Divi-managed)

- **Index:** Pages → **News & updates** → Edit with Divi (Blog module + banner)
- **Single posts:** Divi → Theme Builder → **AS All Posts** (title, content, back link)
- **Stories:** Posts → Add New / Edit (excerpts show on the News list)

Re-run:

```bash
wp eval-file wordpress-migration/setup-news.php
wp eval-file wordpress-migration/fill-excerpts.php
```


1. **Stripe** — GF Stripe is installed; donate form (#2) has one-time + monthly feeds. Confirm test/live mode and run a $1 test gift on `/donate/`
2. **Intake form** — keep linking to live `/intake-form/` until a secure GF rebuild is approved
3. **Divi Theme Builder** (optional polish) — Global Header/Footer layouts in Visual Builder; content already uses brand HTML sections
4. **People bios / portraits** when ready
5. Point `autismsanctuary.org` only after QA sign-off

### Re-run Stripe donate wiring

```bash
wp eval-file wordpress-migration/setup-stripe-donate.php
```

## Isolation

Never write to `/home/sites/autismsanctuary` (production) from these scripts.
