---
name: modular-architecture
description: "Use when planning or implementing modular-monolith refactors in this Laravel application, including defining module boundaries, moving domain code, preserving APIs, adding contracts or events, updating providers, or reviewing cross-module dependencies."
---

# Modular Architecture

Apply this skill when changing the application structure, not when adding ordinary feature code inside an established module.

## Default target

- Keep one Laravel application, one database, and one deployment.
- Place new domain code under `app/Modules/<Context>` using simple feature slices.
- Keep root route files as compatibility entry points.
- Register module providers explicitly in `bootstrap/providers.php`.
- Keep migrations in `database/migrations` unless a later approved decision changes this.
- Do not add a module-management package or create Composer packages until a boundary has proved stable.

The current target contexts are `Identity`, `Events`, `Finance`, `Affiliates`, and `Content`. `Store` and `LMS` are future contexts; do not implement them during the current refactor.

## Required workflow

1. Read `docs/architecture/modular-refactor-baseline.md` and inspect the current code before moving anything.
2. Assign every moved class to one owning context. Do not move a class merely to make folders look uniform.
3. Build a dependency matrix and identify direct model imports, relationships, service lookups, observers, jobs, events, and polymorphic types.
4. Move one context at a time and update every import, type, relationship, factory, seeder, policy, route controller, Filament resource, listener, job, and test.
5. Keep public route names, URIs, middleware, API Resources, request formats, response shapes, and status codes unchanged.
6. Run the smallest affected tests after each context, then run the full verification suite at the checkpoint.

## Boundary rules

- A context owns its Models, Actions, Services, Contracts, HTTP classes, Filament classes, and domain tests.
- New code must not import another context's internal Model, Controller, Filament Resource, or Service.
- Use a Contract, query service, DTO, or domain event for cross-context communication.
- Prefer constructor dependency injection over `app()` lookups.
- Keep `App\Support` and `App\Contracts` limited to genuinely cross-cutting concerns; do not create a shared business-model dump.
- Keep dependencies one-way and reject circular context dependencies.
- Finance owns payment and ledger abstractions; external gateways are adapters behind those abstractions.
- Dispatch persisted-domain events after commit when appropriate and make listeners idempotent.

## Compatibility and data safety

- Do not rename existing tables or rewrite migrations that may have run.
- Inspect polymorphic `*_type` columns before changing model namespaces and preserve an explicit morph map where needed.
- Inspect queued jobs, notifications, events, and serialized configuration for old class names before deployment.
- Keep root routes stable while controllers and resources move into contexts.
- Update Filament panel registration explicitly when resources leave `app/Filament`.
- Preserve Blade view names and Livewire aliases unless a separate, tested frontend migration is approved.

## Verification

Use the existing Laravel, Pest, Larastan, and Pint conventions. When available, use Pest architecture tests to enforce namespace and dependency rules.

At every refactor checkpoint verify:

```text
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --test --format agent
```

Compare route names and API contract tests against the baseline before declaring a context complete. Existing baseline failures must be recorded separately rather than silently attributed to the refactor.

## Avoid

- Big-bang rewrites or microservices.
- Event Sourcing without a concrete business requirement.
- Repositories for every Eloquent model.
- Automatic module discovery when explicit providers are sufficient.
- Direct cross-context Eloquent joins added for convenience.
- New Store or LMS business code during the current refactor.
- Unapproved Composer dependencies.
