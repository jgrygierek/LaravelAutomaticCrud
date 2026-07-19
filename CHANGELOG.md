# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Route binding field support for `show`, `update`, and `destroy`: a route defined with an explicit binding field (e.g. `Route::get('orders/{order:uid}', ...)`) is now looked up by that column instead of the model's route key.

### Changed

- `CrudableController::findModel()` now applies `modifyQuery()` before looking up a record, so `show`, `update`, and `destroy` respect the same query scoping (e.g. tenant or ownership filters) as `index`.
