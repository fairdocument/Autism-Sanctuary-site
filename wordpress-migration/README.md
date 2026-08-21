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
- Pages: Home, About, People, Programs, Our farm, Admissions, Resources, Careers, Fellowship, Donate, Thanks, Contact, Privacy, Terms, News
- Gravity Forms: **Inquiry** (`?intent=` prepopulate), **Donate** (Stripe one-time + monthly)
- Primary + Footer menus
- News via Divi Blog module + Theme Builder single-post template

Brand CSS is loaded by mu-plugin `wp-content/mu-plugins/as-brand-css.php` from `wordpress-migration/custom.css`.

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
- `setup-stripe-donate.php` — Donate form + Stripe feeds
- `setup-news.php` — Divi News page + Theme Builder posts
- `fill-excerpts.php` — rebuild post excerpts from Divi content
- `polish-design.php` — checklist/people markup tweaks

## Before domain cutover

1. Confirm Stripe test/live mode with a small gift on `/donate/`
2. Intake still links to live `/intake-form/` until rebuilt in GF
3. Optional: Divi Theme Builder global header/footer
4. People bios/portraits when ready
5. Point `autismsanctuary.org` only after QA sign-off

## Isolation

Never write to `/home/sites/autismsanctuary` (live production) from these scripts. EmDash (`autismsanctuary-new`) is retired and should not receive deploys.
