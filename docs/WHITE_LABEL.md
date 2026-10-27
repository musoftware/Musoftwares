# White label

Partners sell our desktop programs under their own name. Code: `Modules/SoftwareStore` (`WhiteLabelService`, `WhiteLabelController`, `WhiteLabelStoreController`, `views/white-label/*`).

## Money
- Subscription: 1,000 EGP for 30 days, taken from the partner's wallet (`POST /white-label/subscribe`). Paying again adds 30 days on top.
- The partner's price can equal ours or be higher, never lower. An empty price means our price.
- Share per paid order = 30% of our price + (partner price − our price). It is stored on the order when the order is created (`base_amount`, `partner_commission`) and becomes an `Earning` when the order is paid. The usual `earnings:clear` job moves it to the wallet after 14 days.
- Partner orders get no abandoned-order reminder email and no recovery discount, because both carry our name.

## Pages
| URL | What |
| --- | --- |
| `/white-label` | Public offer page (SEO, sitemap) |
| `/white-label/settings` | Partner page: subscription, brand, prices, embed code, sales |
| `/shop/{slug}` | Partner store. `?embed=1` hides the header for the iframe. Framing is allowed for `/shop/*` (`RemoveSecurityHeaders`, `.htaccess`). |
| `/shop/{slug}/embed.js` | The partner's one-line embed: `<script src=".../shop/{slug}/embed.js" async></script>` (optional `data-lang="ar"`). It puts the store in a frame where the line is, and the store posts its height (`wl-store-height`) so the frame fits it. A plain `<iframe>` is offered too, for sites that block scripts. |
| `/shop/{slug}/{product}` | Checkout. Opens in its own tab from the frame, so the session cookie and the Kashier page work. |
| `/shop/{slug}/{product}/download` | The latest setup, named `{file}.wl-{slug}.exe`. Each download is logged in `white_label_downloads`. |

A closed store (subscription ended) returns 404. The result page after payment shows the partner's brand and WhatsApp.

## Programs (desktop)
The program shows the partner's brand when:
- its license was bought from the partner (the paid order tags the license with `white_label_partner_id`), or
- it has no license yet and was downloaded from the partner's store (`serial_devices.white_label_partner_id`).

A device learns its store once, on check-in: from `setup_name` (the setup file name, written by each program's `build/installer.nsh` and sent by SerialSystem), or, for programs built before that, from a store download made from the same IP in the last 48 hours on the device's first check-in. A license bought from us always shows our brand.

While the partner's subscription is active, `POST /api/serial/device` returns:

```json
"brand": {
  "name": "Nile Soft",
  "logo_url": "https://www.musoftwares.com/uploads/white-label/....png",
  "accent_color": "#ff5500",
  "whatsapp_number": "201112223333",
  "email": "help@nilesoft.com",
  "website_url": "https://nilesoft.com",
  "store_url": "https://www.musoftwares.com/shop/nile-soft"
}
```

and replaces `logo_url`, `whatsapp_number` and (when not active) `purchase_url` with the partner's; `purchase_url` is the partner's page for that program with `?device=`, so the payment activates the device by itself. When there is no partner, or the subscription ended, `brand` is `null` and our values come back.

Programs that already show `logo_url` and `whatsapp_number` pick this up with no update. To hide our name completely, each program should:
1. Use `brand.name` as the window title and in the About box when `brand` is not null.
2. Use `brand.accent_color` for the main color.
3. Point "Renew" / "Buy" to `purchase_url` (already the partner's store when branded).
4. Cache the last `brand` so the program keeps the partner's look when offline.

