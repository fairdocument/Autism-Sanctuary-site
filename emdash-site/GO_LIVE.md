# Autism Sanctuary EmDash — go-live checklist

## Done by Cursor

- [x] Theme, hybrid IA pages, farm storytelling, branding, media
- [x] Multi-intent inquiry form (`/api/inquiry` → EmDash contact-form plugin)
- [x] Stripe Checkout UI + `/api/donate/checkout` (requires `STRIPE_SECRET_KEY`)
- [x] Synced to `/home/sites/autismsanctuary-new/public_html` only

## Required from you (Hub)

1. Open the **autismsanctuary-new** EmDash site on `nimbusserver`
2. Add `STRIPE_SECRET_KEY` (restricted or secret key from the nonprofit Stripe account) to site/hosting env
3. After code deploy (rsync), click **Restart** in Hub. Use **Rebuild** only if deps are broken or Hub requires it — login should work after Restart once session paths are runtime-resolved.
4. Hard-refresh https://autismsanctuary-new-nimbusserver.tempurl.host/

## CMS content restore (news + editable pages)

After deploy, restore WordPress news bodies and key page copy into EmDash:

```bash
ssh -i ~/.ssh/cursor_wpmudev_ed25519 -o IdentitiesOnly=yes cursor@nimbusserver.tempurl.host \
  'cd /home/sites/autismsanctuary-new/public_html && node scripts/restore-cms-content.mjs'
```

Then **Restart** in Hub. Edit Pages / News in `/_emdash/admin`.

## Then verify

- [ ] Home shows Autism Sanctuary hero (not “My Blog”)
- [ ] `/programs` `/our-farm` `/donate` `/contact` work
- [ ] Inquiry form submits; check Form Submissions / email
- [ ] Donate checkout redirects to Stripe when key is set
- [ ] Mobile + desktop hero looks correct
- [ ] Domain cutover for autismsanctuary.org only after sign-off
