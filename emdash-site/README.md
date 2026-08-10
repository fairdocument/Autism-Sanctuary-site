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
