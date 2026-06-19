# Spin Wheel App Design

## Overview

Build a lightweight spin-wheel web app using Laravel, Blade, Alpine.js, and SQLite.
The app has one shared admin area that can manage multiple campaigns. Each campaign has its
own item list, public token, QR code, and last spin result. End users scan a campaign QR code,
open a public mobile-friendly page, and spin to receive a result.

The system does not track inventory, redemptions, or full spin history. Staff will manually edit
the campaign item list whenever an item should no longer be available. The only spin constraint is
that a campaign must not return the same result twice in a row.

## Goals

- Let staff create and manage multiple campaigns from one admin area.
- Let staff maintain each campaign's item list directly in the app.
- Generate a public QR code per campaign that opens the spin screen on mobile.
- Prevent the same item from being returned in two consecutive spins for the same campaign.
- Keep deployment simple with Laravel + SQLite and no extra infrastructure.

## Non-Goals

- No user accounts for public players.
- No inventory or quantity tracking per item.
- No redemption workflow.
- No full analytics or persistent spin history.
- No real-time synchronization across clients beyond normal request/response behavior.

## Architecture

Use a single Laravel application with server-rendered Blade views.

Main layers:
- Admin web routes for campaign CRUD, item editing, and QR display.
- Public web route keyed by a campaign `public_token`.
- A small spin endpoint that chooses and returns the next result for a campaign.
- SQLite for persistence.
- Alpine.js on the public page for simple interaction and wheel animation state.

This keeps the app deployable as a standard Laravel site behind Nginx and PHP-FPM, without a
separate frontend build pipeline beyond normal Laravel assets.

## Data Model

### campaigns
- `id`
- `name`
- `slug` or admin-safe identifier for URLs in the admin area
- `public_token` unique random token used in the public QR link
- `is_active` boolean
- `last_result_item_id` nullable foreign key to `campaign_items.id`
- `created_at`
- `updated_at`

### campaign_items
- `id`
- `campaign_id`
- `label`
- `sort_order`
- `is_active` boolean
- `created_at`
- `updated_at`

`last_result_item_id` is enough to enforce the only business rule. No separate spin-history table
is required for the first version.

## Admin Experience

### Campaign list
- Show all campaigns.
- Allow create, edit, activate/deactivate, and delete.
- Show each campaign's public link and QR code access point.

### Campaign editor
- Edit campaign name and active state.
- Add, remove, reorder, and toggle active items.
- Show the public URL.
- Render a QR code for that public URL.
- Allow staff to copy the public URL quickly.

Admin pages can stay minimal and functional. No need for a custom SPA.

## Public Spin Experience

Public route format: `/play/{public_token}`.

Screen behavior:
- Validate that the campaign exists and is active.
- Load active items for that campaign.
- Render a mobile-first spin wheel UI.
- When the user taps spin, call the spin endpoint.
- Animate the wheel client-side, then reveal the returned result.
- Allow spinning again without page reload.

The wheel UI should derive segments from the active item list returned by the server-rendered page.

## Spin Logic

Rules for one spin request:
- Load active items for the campaign.
- If there are no active items, return an error state.
- If there is exactly one active item, return that item even if it matches the previous result.
  This exception is necessary because there is no alternative.
- If there are two or more active items, randomly select from the active items excluding the last
  returned item.
- Save the selected item's id into `campaigns.last_result_item_id`.
- Return the selected item in the response.

This guarantees no immediate duplicate result when the campaign has at least two active items.

## Concurrency and Consistency

The app does not need strong fairness or audit-grade guarantees, but it should avoid obvious race
conditions during simultaneous spins.

Implementation expectation:
- Wrap the selection and `last_result_item_id` update in a database transaction.
- Lock the campaign row during spin selection if supported cleanly by Laravel's query layer for the
  chosen database path.

SQLite has limited concurrency compared with MySQL, but this is acceptable for a lightweight
campaign app. If usage grows significantly, the storage layer can be moved to MySQL without changing
the user-facing flow.

## Error Handling

Admin side:
- Validate campaign name presence.
- Require at least one item before a campaign should be considered ready to use.

Public side:
- Inactive or missing token returns a friendly unavailable page.
- Empty item list returns a friendly message instead of a broken wheel.
- Spin endpoint returns a structured error if no eligible items exist.

## Security

- Public access uses unguessable `public_token` values.
- Admin area should use Laravel authentication if this app is exposed outside a trusted internal
  environment.
- CSRF protection remains enabled for admin forms.
- Public spin endpoint should still use normal Laravel request validation and rate limiting if the
  app is internet-facing.

Authentication details are intentionally left minimal for the first version, but the code should be
structured so admin auth can be enabled cleanly.

## Testing Strategy

Feature tests should cover:
- Creating and updating campaigns.
- Managing campaign items.
- Public play page for active and inactive campaigns.
- Spin returns one of the active items.
- Spin does not repeat the last result when two or more active items exist.
- Single-item campaign returns that single item.
- Empty campaign returns an error response.

Unit tests are optional for the first pass; feature tests around the spin service are sufficient.

## Deployment Shape

- Laravel application deployed as one site.
- SQLite database stored in Laravel's standard writable storage path.
- Nginx + PHP-FPM.
- No Redis, queue worker, websocket server, or separate frontend host required.

## Future Extensions

These are intentionally out of scope for the first version, but the design should not block them:
- MySQL migration for higher concurrency.
- Spin history and reporting.
- Prize inventory counts.
- Staff roles and permissions.
- Campaign branding/themes.
- One-time or limited-use QR experiences.
