# Changelog

## 1.2.0

### Added

- `ExportTrait` adds an optional `export()` action streaming a CSV download.

## 1.1.0

### Added

- `UpsertTrait` adds an optional `upsert()` action.

### Fixed

- Convention-based events are now dispatched after the enclosing transaction commits, instead of possibly before an outer transaction finishes.

## 1.0.0 (2026-08-25)

### Added

- Route binding field support for `show`, `update`, and `destroy`.
- Convention-based event dispatching for `store`, `update`, and `destroy`, with `getEventNamespace()`/`getEvents()` overrides.
- `withoutGlobalScopes()` hook.
- `createModel()`, `updateModel()`, and `deleteModel()` persistence hooks.

### Changed

- `findModel()` now applies `modifyQuery()`, so `show`, `update`, and `destroy` respect the same query scoping as `index`.
- `findModel()` is now `protected` instead of `private`.
- `store()` and `update()` now build the resource response after the database transaction commits.
- `applyRequest()` now memoizes the resolved request, so resolving it more than once no longer re-validates it.
- `store()` now resolves and validates the request before opening the database transaction.
- Named configuration selection is now controlled via `getConfigName()` instead of the `$configName` property.

### Fixed

- `AutomaticCrudServiceProvider` is now registered under `extra.laravel.providers` in `composer.json`, so Laravel's package auto-discovery picks it up.
- `dispatchEvent()` now throws when a class explicitly mapped in `getEvents()` doesn't exist, instead of silently skipping it.
