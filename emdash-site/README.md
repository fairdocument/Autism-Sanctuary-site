# Autism Sanctuary — EmDash site (RETIRED)

> **No longer in use.** The live redesign is WordPress/Divi at  
> https://autismsanctuary2-nimbusserver.tempurl.host/  
> See the repo root [`README.md`](../README.md) and [`wordpress-migration/`](../wordpress-migration/).  
> Do not deploy this folder to `autismsanctuary-new` or point the public domain here.

Astro + EmDash CMS theme (archive). Historical staging was https://autismsanctuary-new-nimbusserver.tempurl.host/.

## Local development (archive only)

```bash
cd emdash-site
npm install
# Optional: copy remote .env or create local data.db via `npx emdash dev`
npm run build
```

### Editable homepage, header, and footer

- **Homepage blocks:** edit in `/_emdash/admin` → **Pages** → **home**. Click a layout block (Hero, Split Feature, etc.) to open its form. Checklist/feature cards use repeaters. The public homepage always shows the designed layout (even when logged in); do not expect frontend visual-edit outlines on those custom blocks.
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

Then **Restart** the EmDash site in WPMU Hub (usually enough after rsync). Use **Rebuild** only if native deps failed (e.g. `better-sqlite3`) or Hub asks for it.

Sessions are stored under `.astro/sessions` on the server at runtime, so a local build + rsync should not break admin login.

## Stripe

Set `STRIPE_SECRET_KEY` in the hosting env (Hub / `/etc/hosting/env/autismsanctuary-new.env`). Checkout lives at `/api/donate/checkout`.

## Isolation

Never write to `/home/sites/autismsanctuary` (live WordPress) or other sibling sites.
