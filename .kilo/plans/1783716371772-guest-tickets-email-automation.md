# Plan: Email-Driven Guest Tickets (IMAP ingest + SMTP reply)

## Goal
Make `/admin/guest-tickets` a fully email-driven workflow:

1. **Inbox (IMAP)**: Pull replies sent by guests to `admin@musoftwares.com` and attach them to the matching guest ticket.
2. **Outbox (SMTP)**: When an admin replies from the ticket page, send the reply by email to the guest's address (and optionally CC a ticket inbox).
3. **Polish**: Status workflow (`pending` → `replied` → `closed`), mobile-friendly admin UI, full feature/Playwright coverage.

## Audit Findings (current state)

- `app/Http/Controllers/Admin/GuestTicketController.php` only exposes `index` + `show`; no reply, status change, or delete actions.
- `resources/js/Pages/Admin/GuestTickets/Index.tsx` lists `name`, `email`, `status`, `created_at`. No filters, no actions.
- `resources/js/Pages/Admin/GuestTickets/Show.tsx` is read-only — no reply form, no status toggle.
- Two parallel creation paths exist:
  - `GuestTicketSubmissionController::store` writes `guest_tickets` (no email sent).
  - `SupportTicketService::createGuestTicket` writes `tickets` with `anonymous_email` + FCM push + DB notification.
- No IMAP polling job; no inbound email address is configured.
- `composer.json` has no IMAP library.
- `.env` has `MAIL_*` SMTP (Hostinger) but no `IMAP_*` keys; `.env.example` has no mail blocks at all.
- Route `Route::resource('guest-tickets', ...)->only(['index','show'])` lacks reply/status/close.
- Sidebar link `AppSidebar.tsx:93` is already wired.

## Design Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Single source of truth for guest tickets | Promote `guest_tickets` table to the canonical store | `tickets.anonymous_*` was a workaround; separate table is cleaner and already has SoftDeletes. |
| Reply storage | New `guest_ticket_messages` table (inbound + outbound) | Replaces ad-hoc notes; preserves thread for both directions. |
| Email transport | Reuse existing Hostinger SMTP via Laravel `Mail` facade | Already configured and works in `.env`. |
| IMAP library | `webklex/laravel-imap` | Mature Laravel-native IMAP client with artisan commands and a sync API. |
| Inbox strategy | Scheduled artisan command (`imap:pull`) run every 1–5 min via `routes/console.php` scheduler; manual `imap:pull` artisan command for ops | Avoids new dependencies (no queue worker changes); works with Hostinger shared mailbox. |
| Thread matching | RFC822 `In-Reply-To` / `References` headers + `Message-ID` we generate + fallback to `Subject` regex `[GuestTicket#ID]` | Reliable; subject tag is a human-friendly fallback. |
| Status enum | `pending` → `replied` → `closed` (existing `status` column, add enum class) | Matches current UI badge colors. |
| Outbound envelope | `From: admin@musoftwares.com`; `Reply-To: guest-tickets+{id}@musoftwares.com`; `Message-ID` = `guest-ticket-{id}-{uuid}@musoftwares.com`; `References` chain previous IDs | Lets incoming replies match without DB lookup; plus-addressing keeps threading on the single mailbox. |
| Inbound notification | DB notification only (no email loop) | User decision: avoid noise + loops. |
| Attachments | Support attachments on inbound and outbound (store under `storage/app/guest-tickets/{id}/`) | Matches expectations for a support workflow. |
| Authz | Admin can read all; reply requires `moderator` middleware (already on the group) | Reuse existing route group. |

## Affected Boundaries

### Backend (PHP)
- `composer.json` — add `webklex/laravel-imap`.
- `app/Models/GuestTicket.php` — fillable, casts, status enum, relations.
- `app/Models/GuestTicketMessage.php` — new model.
- `app/Enums/GuestTicketStatus.php` — new enum (`pending`, `replied`, `closed`).
- `database/migrations/2026_07_xx_*.php`:
  - add `subject`, `last_message_at`, `message_id` to `guest_tickets`.
  - create `guest_ticket_messages` (id, guest_ticket_id, direction [inbound|outbound], from_email, to_email, subject, body_html, body_text, message_id, in_reply_to, references, headers_json, attachments_json, sent_at, received_at).
- `app/Services/GuestTicketMailer.php` — build + send outbound reply using SMTP, with proper headers.
- `app/Services/GuestTicketInbox.php` — connect IMAP, fetch unseen, match/create messages, mark seen.
- `app/Console/Commands/ImapPullCommand.php` — `php artisan imap:pull`.
- `app/Console/Commands/GuestTicketMailTestCommand.php` — `php artisan guest-tickets:mail-test {ticketId}` (smoke test SMTP).
- `app/Http/Controllers/Admin/GuestTicketController.php` — add `reply`, `updateStatus`, `destroy` actions; load messages on `show`.
- `app/Http/Controllers/GuestTicketSubmissionController.php` — after `create`, send confirmation email to guest and notification to admin mailbox; tag subject with `[GuestTicket#ID]`.
- `app/Notifications/GuestTicketReplyNotification.php` — new, fires DB notification when admin replies.
- `routes/web.php:548` — extend resource: `->only(['index','show','update','destroy'])` plus `POST {guest_ticket}/reply`.
- `routes/console.php` — `Schedule::command('imap:pull')->everyTwoMinutes()->withoutOverlapping()`.

### Frontend (React/Inertia)
- `resources/js/Pages/Admin/GuestTickets/Index.tsx` — add search (name/email/subject), status filter, status badge variants, "Reply" quick action, pagination links.
- `resources/js/Pages/Admin/GuestTickets/Show.tsx` — render thread (messages with inbound/outbound avatars), reply form (textarea + optional attachment), status dropdown (pending/replied/closed), close button, "Open in mailto" link.
- New component `resources/js/Components/Admin/GuestTicket/MessageBubble.tsx` — accessible (ARIA `role="article"`, `aria-label="Inbound message from …"`).
- `resources/js/lib/i18n.ts` keys added via modular arrays (`lang/en/general.php`, `lang/ar/general.php`) — no JSON files.

### Config / Env
- `.env.example` — add the missing mail + imap block:
  ```
  MAIL_MAILER=smtp
  MAIL_SCHEME=null
  MAIL_HOST=smtp.hostinger.com
  MAIL_PORT=465
  MAIL_USERNAME=admin@musoftwares.com
  MAIL_PASSWORD=
  MAIL_ENCRYPTION=ssl
  MAIL_FROM_ADDRESS="admin@musoftwares.com"
  MAIL_FROM_NAME="${APP_NAME}"

  IMAP_HOST=imap.hostinger.com
  IMAP_PORT=993
  IMAP_ENCRYPTION=ssl
  IMAP_USERNAME=admin@musoftwares.com
  IMAP_PASSWORD=
  IMAP_FOLDER=INBOX
  IMAP_LOOKBACK_DAYS=14
  GUEST_TICKET_INBOX=admin@musoftwares.com
  ```
- `.env` — populate the IMAP_* block (real values will come from the user; leave placeholders that are clearly commented as required).
- `config/mail.php` — already supports `MAIL_*`. No edit needed.
- `config/imap.php` — new file, reads the new env keys.

### Tests
- `tests/Feature/AdminGuestTicketReplyTest.php` — admin can post reply, SMTP is dispatched (uses `Mail::fake()`), status flips to `replied`, message persisted.
- `tests/Feature/AdminGuestTicketStatusTest.php` — pending → closed transitions allowed.
- `tests/Feature/GuestTicketInboxTest.php` — given an IMAP fixture (raw RFC822 string), `GuestTicketInbox::processMessage` matches by `In-Reply-To` and by subject tag fallback.
- `tests/Feature/GuestTicketSubmissionEmailTest.php` — submitting the public form sends confirmation to the guest (Mail::fake) and notification to admin mailbox.
- `tests/Feature/SupportAgentRoleIsolationTest.php` — extend with reply + status checks for the moderator role.
- `tests/Playwright/guest-tickets.spec.ts` — open index, filter, open detail, post reply, see outbound bubble, change status to closed.

## Data Flow

**Outbound (admin reply)**
1. Admin POST `/admin/guest-tickets/{id}/reply` with `body`, optional `attachment`.
2. Controller persists `GuestTicketMessage` (direction=outbound), updates `status=replied`, sets `last_message_at`.
3. `GuestTicketMailer::send($ticket, $message)` builds `Mailable` with `In-Reply-To` = previous message-id, `References` chain, `Message-ID` = new one.
4. `Mail::send()` → Hostinger SMTP.

**Inbound (guest reply)**
1. Scheduler runs `imap:pull` every 2 min.
2. `GuestTicketInbox::pull()` connects to `imap.hostinger.com:993`, fetches UNSEEN since `IMAP_LOOKBACK_DAYS`.
3. For each message:
   - Parse `In-Reply-To` → look up `guest_ticket_messages.message_id` → find ticket.
   - Else parse `References` (last id).
   - Else parse subject regex `/\[GuestTicket#(\d+)\]/`.
   - Else create new ticket from `(name, email, subject, body)`.
4. Persist `GuestTicketMessage` (direction=inbound), set `last_message_at`, flip `status=replied` if it was `pending` or `closed`.
5. Save attachments under `storage/app/guest-tickets/{id}/`.
6. Flag message `\Seen` on IMAP.

## Failure Modes & Mitigations

| Risk | Mitigation |
|---|---|
| IMAP credentials wrong | `imap:pull` exits 1 with clear error; surfaced in scheduler output + logs. `tests/Feature` use `Mail::fake()` and a fake `ImapClient`. |
| SMTP auth failure | `Mail::send()` throws; controller returns `back()->withErrors`. Job-level retries via `Mailer` built-ins. |
| Duplicate polling by two schedulers | `withoutOverlapping()` + cache lock (`Cache::lock('imap:pull', 120)`). |
| Missing PHP IMAP extension | Document in README; composer.json requires `ext-imap` via `webklex/laravel-imap`. |
| Hostinger mailbox quota | Cap attachment size to 10 MB per message; reject oversized with logged warning. |
| Status going backward | Enum guards in controller (`pending→replied`, `replied→closed`; `closed` reopen requires explicit action). |
| Spoofed `From` | Trust `from_email` only after matching `In-Reply-To`/`References` to a known ticket; otherwise create new ticket as the *actual* sender. |

## Rollout / Migration

1. Add migration: columns on `guest_tickets` + new `guest_ticket_messages` table (idempotent; default-safe).
2. Backfill: existing rows stay `pending`; `last_message_at = created_at`.
3. Composer: `composer require webklex/laravel-imap` (requires `ext-imap`; document).
4. Deploy env keys; redeploy; test `php artisan imap:pull --dry-run`.
5. Enable scheduler: ensure `* * * * * php artisan schedule:run` cron exists on the host.
6. Run feature + Playwright suite.

## Validation Plan

- `php artisan imap:pull --dry-run` prints the parsed emails without saving.
- `php artisan guest-tickets:mail-test 1` sends a test email to the ticket's address.
- `php artisan test --filter=GuestTicket` passes.
- `npx playwright test tests/Playwright/guest-tickets.spec.ts` passes.
- Manual: submit a public ticket from the homepage → admin sees it → admin replies → guest receives email → guest replies from email client → admin sees inbound bubble.
- `php artisan test` (full) green; `vendor/bin/pint` and `vendor/bin/phpstan analyse` clean for new files.

## Open Questions (need user input before implementation)

_All resolved — see "Confirmed Decisions" above._

1. **IMAP credentials** — Hostinger mailbox `admin@musoftwares.com` — please confirm host (`imap.hostinger.com:993` SSL is the default) and that the same mailbox is used for both inbound and outbound.
2. **Reply-To address** — Should outbound mail use `Reply-To: guest-tickets+museoftwares.com` (plus-addressing) so replies route back through the same IMAP mailbox? Recommended: yes.
3. **Auto-close policy** — Should tickets auto-close after N days of inactivity, or stay open until the admin closes them? Recommended: keep manual close for now, add auto-close later.
4. **Admin notifications on inbound** — Send a DB notification + email to all admins, or only the last replying admin? Recommended: DB notification to all admins; email only to last replier.

## Confirmed Decisions (user input received)

- **IMAP mailbox**: `admin@musoftwares.com` via Hostinger (imap.hostinger.com:993 SSL), reused for inbound + outbound.
- **Outbound envelope**: `From: admin@musoftwares.com`, `Reply-To: guest-tickets+{id}@musoftwares.com` (plus-addressing), so replies land in the same mailbox.
- **Inbound notification**: DB notification only; no extra email to admins.
- **Auto-close**: manual close only for v1.