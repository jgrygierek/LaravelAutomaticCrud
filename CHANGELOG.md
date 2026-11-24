# Changelog

## 1.5.0

### Changed

- Package renamed to `jgrygierek/laravel-automatic-crud`; the `JG\LaravelAutomaticCrud` namespace is unchanged.

## 1.4.0

### Changed

- `upsert()` no longer takes an ID from the route; it targets singleton (1:1) resources identified by the new `getUpsertAttributes()`, and `upsertModel()` now receives those attributes instead of `$id`.
- `?per_page` is capped at `pagination.max_per_page` (`100` by default); override `maxItemsPerPage()` to change it.

### Fixed

- `show`, `update`, and `destroy` qualify the route key column with the model's table, so a join in `modifyQuery()` no longer fails with an ambiguous column error.
- Model resolution removes only the trailing `Controller` from the controller class name (`ControllerLogController` resolves to `ControllerLog`).
- Pagination reads `?pagination`, `?per_page`, and `?page` from the resolved `index` request, so values set in its `prepareForValidation()` apply.
- `export` no longer breaks off mid-download when an array or object value can't be encoded as JSON.
- `export` no longer fails with `500` for an unsorted query on a model without a primary key.
- `store`, `update`, `destroy`, and `upsert` open their transaction on the model's connection instead of the default one.
- A non-boolean `?pagination` no longer disables pagination and a non-integer `?per_page` no longer returns one item per page; both fall back to the defaults.

## 1.3.0

### Added

- `SortingRulesHelper::rules()` returns validation rules restricting `sort_by` and `sort_direction`.

### Fixed

- `show`, `update`, `destroy`, and `upsert` in nested routes now look up the record by the last route parameter instead of the first one.
- `applyRequest()` no longer returns a request memoized during an earlier request when the controller instance is reused.
- An invalid `sort_direction` falls back to `asc` and a non-string `sort_by` is ignored, instead of failing with `500`.

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
