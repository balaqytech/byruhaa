# Modular Context Map

This map is the ownership and dependency reference for the incremental modular refactor. It describes the target boundaries without changing the existing API or database contracts.

## Contexts

| Context | Owns | Current legacy location during migration |
| --- | --- | --- |
| Identity | users, customers, family members, authentication actions and customer policies | `App\\Models`, `App\\Actions\\Fortify`, `App\\Providers\\FortifyServiceProvider` |
| Events | events, bookings, seats, pricing, discounts, coupons and event interest | `App\\Models`, `App\\Actions`, `App\\States\\Booking`, API and public controllers |
| Finance | payment contracts, payment gateways, refunds, ledger accounts, ledger entries and payment posting | `App\\Modules\\Finance`, `App\\Services\\Payments`, `App\\Models`, `App\\Actions` |
| Affiliates | affiliates, referrals, commissions and payouts | `App\\Models`, `App\\Actions`, middleware and Filament resources |
| Content | blog, public content, landing pages and public-site presentation | `App\\Models`, `App\\Http\\Controllers`, `App\\Support` and Filament resources |
| Store | future products, inventory, carts and orders | Not implemented in this refactor |
| LMS | future courses, enrolments and progress | Not implemented in this refactor |

## Dependency direction

```text
                         App\\Support
                             |
       +---------------------+---------------------+
       |                     |                     |
    Identity             Finance              Content
       |                     |                     |
       +----------+----------+                     |
                  |                                |
               Events -----------------------> Affiliates
```

The diagram describes allowed business dependencies, not PHP namespace placement. During migration, legacy classes may still be imported by unmigrated code. New or changed code must use the target direction.

## Boundary rules

- Identity owns customer identity and exposes customer-facing Contracts or read DTOs instead of exposing internal Models.
- Finance owns the payment gateway port. The Thawani implementation remains an adapter until the Finance services are migrated.
- Events owns booking and event behavior and may request customer or payment operations through Contracts.
- Affiliates consumes stable event/payment signals and must not reach into Events or Finance internals.
- Content may render public read models but must not contain booking, payment, or commission workflows.
- `App\\Support` is for framework-neutral utilities and formatting, not shared business workflows.
- `App\\Contracts` is transitional legacy space. New context-specific ports belong to the owning context.

## Transitional allowlist

The following legacy dependencies are allowed until their context migration phase:

- Existing `App\\Models` imports from unmigrated controllers, resources, actions, services, and tests.
- Existing global Actions and Services that are still used by more than one unmigrated context.
- Existing root route files and Filament discovery paths.
- Existing database migration paths and table names.

The allowlist applies only to existing code. New code must follow the module boundaries and use constructor injection, Contracts, query services, DTOs, or after-commit domain events.

## First extracted port

The payment gateway Contract now lives at:

```text
App\\Modules\\Finance\\Contracts\\PaymentGateway
```

Its behavior and method signatures are unchanged. This is the first example of moving a boundary without changing the REST API, database schema, or payment behavior.

## Identity migration checkpoint

The Identity context now owns the `User`, `Customer`, and `FamilyMember` models, Fortify actions and provider, and phone normalization service. Existing controllers, requests, factories, Filament screens, Livewire views, seeders, and tests use the new namespaces while public routes, guards, table names, and API payloads remain unchanged.

Customer webhook records historically stored `App\\Models\\Customer` in the polymorphic type column. `IdentityServiceProvider` keeps that value as a morph-map alias for the new model, so existing records continue to resolve and newly-created records preserve the same database value. Identity factories explicitly bind to their moved models because Laravel's convention-based factory lookup cannot infer models outside `App\\Models`.
