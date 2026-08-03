# Modular Refactor Baseline

Date: 2026-08-04  
Branch: `refactor/modular`
Base commit: `96e0d19` (`Restrict events to published status and migrate course slugs`)

This document records the repository state before any modular-refactor code changes. It is the comparison point for later phases; the failures listed here were observed before Phase 0 changed application code.

## Scope of Phase 0

Phase 0 created the refactor branch, inspected the current architecture, captured verification results, and documented the initial dependency shape. It intentionally did not:

- add Modules or move classes;
- change Composer dependencies;
- change routes, migrations, models, or application behavior;
- fix pre-existing test, Pint, or Larastan failures.

## Runtime and framework

| Item | Baseline |
| --- | --- |
| PHP | 8.4.16 |
| Laravel | 13.16.1 |
| Filament | 5.6.7 |
| Livewire | 4.3.1 |
| Pest | 4.7.3 |
| Larastan | 3.10.0 |
| Composer autoload | `App\\` => `app/` |
| Queue default | `database` |
| Cache default | `database` |
| Database migrations | 51 |

The current `App\\` PSR-4 mapping already covers a future `App\\Modules\\...` namespace; a second Composer autoload root is not required for the planned feature slices.

## Current application shape

The application is a layered monolith with no existing `Modules` or `Domains` namespace.

| Area | Count |
| --- | ---: |
| Models | 30 |
| Actions | 26 |
| Services | 8 |
| Filament PHP files | 112 |
| Tests | 38 |

Current top-level application folders:

```text
Actions, Casts, Concerns, Console, Contracts, Data, Enums, Exceptions,
Filament, Http, Listeners, Livewire, Models, Providers, Services,
Settings, States, Support
```

### Candidate ownership map

| Future context | Current responsibilities |
| --- | --- |
| Identity | `User`, `Customer`, `FamilyMember`, Fortify actions, customer policies and authentication support |
| Events | `Event`, bookings, seats, pricing, discounts, coupons, event interest and booking workflows |
| Finance | payments, refunds, Thawani, ledger accounts/entries/transactions and payment posting |
| Affiliates | affiliate accounts, referrals, commissions and payout requests |
| Content | blog, public-site controllers, landing pages and public content |
| Store | Future only; no implementation in the refactor |
| LMS | Future only; no implementation in the refactor |

## Route baseline

The application exposes 91 non-vendor routes:

| Group | Routes |
| --- | ---: |
| Admin/Filament | 44 |
| API and webhook | 26 |
| Affiliate | 6 |
| Blog | 3 |
| Events/public event pages | 3 |
| Customer | 2 |
| Other public routes | 7 |

The existing API boundary contains 26 named routes. The names below must remain unchanged while classes are moved:

```text
api.v1.customers.index
api.v1.customers.store
api.v1.customers.show
api.v1.customers.update
api.v1.customers.destroy
api.v1.customers.bookings.index
api.v1.customers.bookings.store
api.v1.customers.bookings.show
api.v1.customers.bookings.payments.store
api.v1.customers.family-members.index
api.v1.customers.family-members.store
api.v1.customers.family-members.show
api.v1.customers.family-members.update
api.v1.customers.family-members.destroy
api.v1.customers.payments.index
api.v1.customers.payments.store
api.v1.customers.payments.show
api.v1.customers.payments.update
api.v1.customers.payments.destroy
api.v1.customers.profile.update
api.v1.events.index
api.v1.events.show
api.v1.integrations.assistant.event-interests.show
api.v1.integrations.assistant.event-interests.update
api.v1.integrations.assistant.event-interests.destroy
api.webhooks.thawani
```

The route files remain the public entry points during later phases:

```text
routes/api.php
routes/web.php
routes/public.php
routes/customer.php
routes/affiliate.php
routes/console.php
```

## Verification baseline

Commands were run from the repository root on this branch.

### Pest

```text
Command: php artisan test --compact
Result: failed
Tests: 268
Passed: 261
Failed: 6
Skipped: 1
Assertions: 2,022
Duration: 344.181 seconds
```

One observed failure is the existing login-page assertion expecting the affiliate-login link in the rendered authentication page. The complete failure set must be captured again in a machine-readable report before Phase 1 if exact per-test comparison is needed.

### Pint

```text
Command: vendor/bin/pint --test --format agent
Result: failed
File: app/Actions/RenderEventLandingPage.php
Reported fixers: function_declaration, concat_space, braces_position, single_line_empty_body
```

### Larastan

```text
Command: vendor/bin/phpstan analyse --no-progress
Result: failed
Errors: 128
```

Representative existing findings include incomplete iterable types, undefined Eloquent properties/methods in API Resources, an unreachable statement in `MoneyBaisaCast`, and enum/string type comparisons in `PublicSiteController`.

## Phase 0 risks to preserve during later work

- Existing test failures must not be attributed to namespace moves without a before/after comparison.
- `App\\Filament` is currently discovered by the admin panel provider; moving resources requires an explicit discovery/registration check.
- Authentication, API Resources, model relationships, queued payloads, and polymorphic model types are compatibility-sensitive.
- The working tree contained pre-existing untracked files under `.codex-work/` and `docs/requirements/`; they were not modified.

## Commands used to reproduce the baseline

```powershell
php artisan route:list --except-vendor --json
composer show --direct --format=json
php artisan test --compact
vendor/bin/pint --test --format agent
vendor/bin/phpstan analyse --no-progress
```
