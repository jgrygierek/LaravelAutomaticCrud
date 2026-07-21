# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Route binding field support for `show`, `update`, and `destroy`: a route defined with an explicit binding field (e.g. `Route::get('orders/{order:uid}', ...)`) is now looked up by that column instead of the model's route key.
- Convention-based event dispatching: `store`, `update`, and `destroy` now dispatch `{event_namespace}\{ModelName}CreatedEvent`/`UpdatedEvent`/`DeletedEvent` with the affected model when the class exists. Configurable via `namespaces.event`, overridable via `getEventNamespace()` and, per action, `getEvents()` — which defaults to `[]` and merges with the convention rather than replacing it.
- `withoutGlobalScopes()` hook: override it to exclude specific global scopes from every query the controller builds (`index`, `show`, `update`, `destroy`). Defaults to `[]`.
- `createModel()`, `updateModel()`, and `deleteModel()` hooks: `store`, `update`, and `destroy` now delegate the actual write to these protected methods, so side effects can be added around the write without duplicating the surrounding transaction, event dispatch, or response handling.

### Changed

- `CrudableController::findModel()` now applies `modifyQuery()` before looking up a record, so `show`, `update`, and `destroy` respect the same query scoping (e.g. tenant or ownership filters) as `index`.
- `findModel()` is now `protected` instead of `private`, so it can be overridden in a subclass.
- `store()` and `update()` now build the resource response after the database transaction commits, instead of inside it.
- `applyRequest()` now memoizes the resolved request on an instance property. Calling it more than once in the same request lifecycle (directly and/or via `getAllowedRequestValues()`/`validateRequest()`) returns the same instance instead of resolving — and re-validating — a new one.
- `store()` now resolves and validates the request before opening the database transaction that creates the model, instead of doing so inside it.

### Fixed

- Registered `AutomaticCrudServiceProvider` under `extra.laravel.providers` in `composer.json`, so Laravel's package auto-discovery actually registers it in consuming applications. It was previously missing, meaning config merging, config validation, and `vendor:publish` never ran outside this repo's own test suite (which registers the provider manually via Testbench).
