# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Route binding field support for `show`, `update`, and `destroy`: a route defined with an explicit binding field (e.g. `Route::get('orders/{order:uid}', ...)`) is now looked up by that column instead of the model's route key.

### Changed

- `CrudableController::findModel()` now applies `modifyQuery()` before looking up a record, so `show`, `update`, and `destroy` respect the same query scoping (e.g. tenant or ownership filters) as `index`.
- `findModel()` is now `protected` instead of `private`, so it can be overridden in a subclass.
- `store()` and `update()` now build the resource response after the database transaction commits, instead of inside it.

### Fixed

- `show` and `destroy` now run form request validation/authorization before looking up the record, so an unauthorized or invalid request returns 403/422 instead of a 404 that would otherwise leak whether the record exists.
