# Autism Sanctuary — EmDash site

Astro + EmDash CMS theme for Autism Sanctuary, deployed to WPMU Unlimited Hosting.

- **Staging:** https://autismsanctuary-new-nimbusserver.tempurl.host/
- **Remote root (only):** `/home/sites/autismsanctuary-new/public_html`
- **SSH:** `cursor@nimbusserver.tempurl.host` with `~/.ssh/cursor_wpmudev_ed25519`

## Local development

```bash
cd emdash-site
npm install
# Optional: copy remote .env or create local data.db via `npx emdash dev`
npm run build
```

### Editable homepage, header, and footer

- **Homepage blocks:** edit the `home` page in `/_emdash/admin` — click a layout block (Hero, Split Feature, etc.) to open its form. Checklist/feature cards use repeaters. Do not use frontend visual editing for the homepage (it would show placeholders instead of the real layout).
- **Header nav:** Menus → **Primary**
- **Footer link columns:** Menus → **Footer Explore**, **Footer Engage**, **Footer Legal**
- **Site name / tagline / social:** Settings
- After deploying seed/menu changes, run `node scripts/restore-cms-content.mjs --pages-only` on the host (restores pages + menus).

## Deploy

```bash
npm run build
rsync -avz --delete \
  --exclude node_modules --exclude data.db --exclude .env --exclude uploads \
  -e "ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes" \
  ./ cursor@nimbusserver.tempurl.host:/home/sites/autismsanctuary-new/public_html/
```

Then **Restart** (or **Rebuild**) the EmDash site in WPMU Hub.

## Stripe

Set `STRIPE_SECRET_KEY` in the hosting env (Hub / `/etc/hosting/env/autismsanctuary-new.env`). Checkout lives at `/api/donate/checkout`.

## Isolation

Never write to `/home/sites/autismsanctuary` (live WordPress) or other sibling sites.
