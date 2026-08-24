# Laravel Automatic CRUD

![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12%20|%2013-FF2D20?logo=laravel&logoColor=white)
[![Code Style](https://github.com/jgrygierek/LaravelAutomaticCrud/actions/workflows/code-style.yml/badge.svg)](https://github.com/jgrygierek/LaravelAutomaticCrud/actions/workflows/code-style.yml)
[![Tests](https://github.com/jgrygierek/LaravelAutomaticCrud/actions/workflows/tests.yml/badge.svg)](https://github.com/jgrygierek/LaravelAutomaticCrud/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/jgrygierek/LaravelAutomaticCrud/branch/master/graph/badge.svg)](https://codecov.io/gh/jgrygierek/LaravelAutomaticCrud)

Automatic CRUD controllers with custom configurations for Laravel 12 and 13.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
    - [Controllers](#controllers)
        - [Creating a controller](#creating-a-controller)
        - [Namespace override](#namespace-override)
        - [Model class override](#model-class-override)
        - [Pagination and sorting](#pagination-and-sorting)
        - [Customizing the query](#customizing-the-query)
        - [Looking up records by a custom key](#looking-up-records-by-a-custom-key)
        - [Skipping global scopes](#skipping-global-scopes)
        - [Customizing persistence](#customizing-persistence)
        - [Upsert](#upsert)
        - [Filtering with SearchableInterface](#filtering-with-searchableinterface)
    - [Requests](#requests)
        - [Namespace override](#namespace-override-1)
        - [Request class convention](#request-class-convention)
        - [Force custom requests](#force-custom-requests)
        - [Only validated fields](#only-validated-fields)
        - [Reusing an already-resolved request](#reusing-an-already-resolved-request)
    - [Resources](#resources)
        - [Namespace override](#namespace-override-2)
        - [Resource class convention](#resource-class-convention)
        - [Resource class override](#resource-class-override)
    - [Events](#events)
        - [Namespace override](#namespace-override-3)
        - [Event class convention](#event-class-convention)
        - [Event class override](#event-class-override)
- [Configuration](#configuration)
    - [Custom configurations](#custom-configurations)
- [Development](#development)
    - [Without Docker](#without-docker)
    - [With Laravel Sail (Docker)](#with-laravel-sail-docker)

## Installation

```bash
composer require jg/laravel-automatic-crud
```

Laravel auto-discovers the service provider. Optionally publish the config file:

```bash
php artisan vendor:publish --tag=automatic-crud-config
```

## Usage

### Controllers

#### Creating a controller

Extend `CrudableController` and define a route resource. The controller resolves the model, form request, and resource classes by convention.

```php
use JG\LaravelAutomaticCrud\Http\Controllers\CrudableController;

class ItemController extends CrudableController
{
    //
}
```

```php
// routes/api.php
Route::apiResource('items', ItemController::class);
```

---

#### Namespace override

Override `getModelNamespace()` in the controller to resolve the model from a different namespace without touching the config:

```php
class ItemController extends CrudableController
{
    protected function getModelNamespace(): string
    {
        return 'Domain\Inventory\Models';
    }
}
```

Priority order (highest to lowest):

1. `getModelNamespace()` controller override
2. Custom configuration value
3. Config default (`namespaces.model`)
4. Hardcoded fallback (`App\Models`)

#### Model class override

Override `getModelClass()` to bypass both the namespace method and the naming convention entirely:

```php
class ItemController extends CrudableController
{
    protected function getModelClass(): string
    {
        return Item::class;
    }
}
```

#### Pagination and sorting

**Pagination** is enabled by default and returns 10 items per page wrapped in `{ data, links, meta }`. Disable it to get a plain `{ data }` array.

**Sorting** is triggered by `?sort_by=<field>`. Direction defaults to `asc` when omitted. Sorting is skipped when the query already has an `orderBy` (e.g.
applied inside `modifyQuery()`).

Supported query parameters:

| Parameter         | Values          | Default | Description                  |
|-------------------|-----------------|---------|------------------------------|
| `?pagination`     | `true`, `false` | `true`  | Enable or disable pagination |
| `?per_page`       | integer         | `10`    | Number of items per page     |
| `?sort_by`        | column name     | —       | Column to sort by            |
| `?sort_direction` | `asc`, `desc`   | `asc`   | Sort direction               |

Override pagination defaults in the controller:

```php
class ItemController extends CrudableController
{
    protected function defaultIsPaginationEnabled(): bool
    {
        return false;
    }

    protected function defaultItemsPerPage(): int
    {
        return 25;
    }

    protected function isPaginationOverrideAllowedInQuery(): bool
    {
        return false;
    }

    protected function isPerPageOverrideAllowedInQuery(): bool
    {
        return false;
    }
}
```

Priority order (highest to lowest) for both pagination and per-page:

1. Query parameter (`?pagination=`, `?per_page=`)
2. Controller method override (`defaultIsPaginationEnabled()`, `defaultItemsPerPage()`)
3. Config value (`pagination.paginate`, `pagination.per_page`)

#### Customizing the query

Override `modifyQuery()` to apply additional constraints before pagination:

```php
class ItemController extends CrudableController
{
    protected function modifyQuery(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
```

`modifyQuery()` is also applied when `show`, `update`, and `destroy` look up a record, so scoping such as multi-tenancy or ownership filters is enforced consistently everywhere, not just on `index`.

#### Looking up records by a custom key

`show`, `update`, and `destroy` look up the record by the model's route key (`getRouteKeyName()`, which falls back to the primary key by default). To look up by a different column on a specific route — without changing the model globally — use Laravel's route binding field syntax:

```php
// routes/api.php
Route::get('orders/{order:uid}', [OrderController::class, 'show']);
Route::put('orders/{order:uid}', [OrderController::class, 'update']);
Route::delete('orders/{order:uid}', [OrderController::class, 'destroy']);
```

The controller keeps the usual `show(int|string $id)` signature — the `uid` segment is resolved automatically from the route definition. Routes without an explicit binding field keep using the model's route key.

`findModel()` is `protected`, so it can be overridden entirely if the lookup logic above doesn't fit (e.g. querying across multiple tables).

#### Skipping global scopes

`index`, `show`, `update`, and `destroy` all apply any global scopes registered on the model. Override `withoutGlobalScopes()` to exclude specific global scopes across all four:

```php
class OrderController extends CrudableController
{
    protected function withoutGlobalScopes(): array
    {
        return [PublishedScope::class];
    }
}
```

Applies uniformly everywhere the model is queried — there's no per-action opt-out. Defaults to `[]` (no scopes skipped).

#### Customizing persistence

`store`, `update`, and `destroy` delegate the actual write to `createModel()`, `updateModel()`, and `deleteModel()`. Override any of them to add side effects around the write, without duplicating the surrounding transaction, event dispatch, or response handling:

```php
class OrderController extends CrudableController
{
    protected function updateModel(Model $model, array $data): Model
    {
        if (array_key_exists('status', $data) && $model->status !== $data['status']) {
            $model->tokens()->delete();
        }

        return parent::updateModel($model, $data);
    }
}
```

If the change goes beyond the persistence step itself (e.g. it also affects which event fires or what's returned), override the whole action (`store()`, `update()`, `destroy()`) instead.

#### Upsert

`upsert(int|string $id)` is not part of `CrudableController` — add it to a specific controller with `UpsertTrait`, then register a route for it manually (it's not part of `Route::apiResource()`):

```php
use JG\LaravelAutomaticCrud\Traits\UpsertTrait;

class ItemController extends CrudableController
{
    use UpsertTrait;
}
```

```php
// routes/api.php
Route::put('items/{item}', [ItemController::class, 'upsert']);
```

It looks up a record by the same route key column `show`/`update`/`destroy` use, independently of `createModel()`/`updateModel()`. It responds `200` when it updated an existing record, `201` when it created one, and dispatches the matching `Created` or `Updated` event. Override `upsertModel(int|string $id, array $data): Model` to customize the write.

If the route key column is the (non-fillable) primary key, creating a new record won't force that key to `$id` — mass assignment silently drops it, same as `store()`. Upsert-by-`$id` reliably targets a specific new record only when the route key column is fillable (e.g. a natural key like `slug`).

#### Filtering with `SearchableInterface`

Implement `SearchableInterface` on a model to enable pipeline-based filtering on the `index` endpoint:

```php
use JG\LaravelAutomaticCrud\Http\Interfaces\SearchableInterface;
use Illuminate\Database\Eloquent\Model;

class Item extends Model implements SearchableInterface
{
    public function searchFilters(): array
    {
        return [
            ActiveFilter::class,
            NameFilter::class,
        ];
    }
}
```

Filter classes receive the query builder through a pipeline:

```php
class ActiveFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        return $next($query->where('active', true));
    }
}
```

##### Config-keyed filters

`searchFilters()` can also return a keyed array to provide different filter sets per named configuration. The key matches the controller's `getConfigName()`; `default` is used as a fallback when no matching key is found:

```php
class Item extends Model implements SearchableInterface
{
    public function searchFilters(): array
    {
        return [
            'default' => [NameFilter::class],
            'api'     => [ActiveFilter::class, NameFilter::class],
        ];
    }
}
```

A controller overriding `getConfigName()` to return `'api'` will get `[ActiveFilter::class, NameFilter::class]`, while all others fall back to `[NameFilter::class]`.

---

### Requests

#### Namespace override

Override `getRequestNamespace()` in the controller to resolve form requests from a different namespace without touching the config:

```php
class ItemController extends CrudableController
{
    protected function getRequestNamespace(): string
    {
        return 'Domain\Inventory\Requests';
    }
}
```

Priority order (highest to lowest):

1. `getRequestNamespace()` controller override
2. Custom configuration value
3. Config default (`namespaces.request`)
4. Hardcoded fallback (`App\Http\Requests`)

#### Request class convention

Given `ItemController`, the package looks for form request classes at:

| Action  | Convention                                           | Example                                      |
|---------|------------------------------------------------------|----------------------------------------------|
| index   | `{request_namespace}\{Models}\Index{Model}Request`   | `App\Http\Requests\Items\IndexItemRequest`   |
| show    | `{request_namespace}\{Models}\Show{Model}Request`    | `App\Http\Requests\Items\ShowItemRequest`    |
| store   | `{request_namespace}\{Models}\Store{Model}Request`   | `App\Http\Requests\Items\StoreItemRequest`   |
| update  | `{request_namespace}\{Models}\Update{Model}Request`  | `App\Http\Requests\Items\UpdateItemRequest`  |
| upsert  | `{request_namespace}\{Models}\Upsert{Model}Request`  | `App\Http\Requests\Items\UpsertItemRequest`  |
| destroy | `{request_namespace}\{Models}\Destroy{Model}Request` | `App\Http\Requests\Items\DestroyItemRequest` |

Form requests are optional by default — if the class does not exist, the standard `request()` is used.

For `show`, `update`, and `destroy`, the record is looked up via `findModel()` before the form request is resolved and validated, so a missing record always returns `404` regardless of the request body or authorization outcome.

#### Force custom requests

Set `requests.force_custom` to `true` to throw a `RuntimeException` when a request class is missing instead of silently falling back:

```php
// config/automatic-crud.php
'requests' => [
    'force_custom' => true,
],
```

#### Only validated fields

Set `requests.only_validated` to `true` (requires `force_custom` to also be `true`) to pass only `$request->validated()` fields to `create()` / `update()`, even
when the form request class is not a `FormRequest`:

```php
// config/automatic-crud.php
'requests' => [
    'force_custom'   => true,
    'only_validated' => true,
],
```

#### Reusing an already-resolved request

`applyRequest()` memoizes the resolved request for the lifetime of the controller instance, so calling it more than once — directly and/or via `getAllowedRequestValues()`/`validateRequest()` — always returns the same instance instead of resolving (and re-validating) a new one. This makes it safe to resolve the request yourself for custom logic in a fully overridden action, and still use `getAllowedRequestValues()` afterwards:

```php
class UserController extends CrudableController
{
    public function update(int|string $id): JsonResource
    {
        $request = $this->applyRequest();
        $user = $this->findModel($id);

        $user->update($this->getAllowedRequestValues());

        if ($request->has('role')) {
            $user->syncRoles([$request->validated('role')]);
        }

        return $this->getResource($user);
    }
}
```

---

### Resources

#### Namespace override

Override `getResourceNamespace()` in the controller to resolve resources from a different namespace without touching the config:

```php
class ItemController extends CrudableController
{
    protected function getResourceNamespace(): string
    {
        return 'Domain\Inventory\Resources';
    }
}
```

Priority order (highest to lowest):

1. `getResourceNamespace()` controller override
2. Custom configuration value
3. Config default (`namespaces.resource`)
4. Hardcoded fallback (`App\Http\Resources`)

#### Resource class convention

Given `ItemController`, the package looks for resource classes at:

| Type                | Convention                                       | Example                                     |
|---------------------|--------------------------------------------------|---------------------------------------------|
| Resource            | `{resource_namespace}\{Model}Resource`           | `App\Http\Resources\ItemResource`           |
| Collection resource | `{resource_namespace}\{Model}CollectionResource` | `App\Http\Resources\ItemCollectionResource` |

If `{Model}Resource` is not found, `JsonResource` is used as a fallback. If `{Model}CollectionResource` is not found, it falls back to `{Model}Resource`, then
`JsonResource`.

#### Resource class override

Override `getResourceClass()` or `getResourceCollectionClass()` to bypass the namespace method and naming convention entirely:

```php
class ItemController extends CrudableController
{
    protected function getResourceClass(): string
    {
        return CustomItemResource::class;
    }

    protected function getResourceCollectionClass(): string
    {
        return CustomItemCollectionResource::class;
    }
}
```

---

### Events

#### Namespace override

Override `getEventNamespace()` in the controller to resolve events from a different namespace without touching the config:

```php
class ItemController extends CrudableController
{
    protected function getEventNamespace(): string
    {
        return 'Domain\Inventory\Events';
    }
}
```

Priority order (highest to lowest):

1. `getEventNamespace()` controller override
2. Custom configuration value
3. Config default (`namespaces.event`)
4. Hardcoded fallback (`App\Events`)

#### Event class convention

Given `ItemController`, the package looks for event classes at:

| Action  | Convention                              | Example                       |
|---------|-----------------------------------------|-------------------------------|
| store   | `{event_namespace}\{Model}CreatedEvent` | `App\Events\ItemCreatedEvent` |
| update  | `{event_namespace}\{Model}UpdatedEvent` | `App\Events\ItemUpdatedEvent` |
| destroy | `{event_namespace}\{Model}DeletedEvent` | `App\Events\ItemDeletedEvent` |

Events are optional — if the class does not exist, nothing is dispatched. When it exists, it's instantiated with the affected model and dispatched through Laravel's event dispatcher, deferred until the surrounding database transaction commits (via `DB::afterCommit()`) — it never fires if the transaction rolls back:

```php
class ItemCreatedEvent
{
    public function __construct(public readonly Model $model) {}
}
```

`index` and `show` never dispatch events.

#### Event class override

Override `getEvents()` to control exactly which event class is dispatched for specific actions. It defaults to an empty array — any action *not* present as a key still falls back to the naming convention. An action mapped explicitly to `null` is disabled, bypassing the convention entirely:

```php
class ItemController extends CrudableController
{
    protected function getEvents(): array
    {
        return [
            'Created' => CustomItemCreatedEvent::class,
            'Deleted' => null,
        ];
    }
}
```

In the example above, `store` dispatches `CustomItemCreatedEvent` instead of the convention class, `destroy` dispatches nothing, and `update` is untouched — it still resolves `ItemUpdatedEvent` by convention, since `'Updated'` isn't a key in the array.

Unlike the convention (which silently skips a missing class), a class mapped explicitly in `getEvents()` must exist — a typo or a class that was renamed/removed throws a `RuntimeException` instead of silently dispatching nothing.

---

## Configuration

```php
// config/automatic-crud.php
return [
    'configs' => [
        'default' => [
            'namespaces' => [
                'model'    => 'App\Models',
                'resource' => 'App\Http\Resources',
                'request'  => 'App\Http\Requests',
                'event'    => 'App\Events',
            ],
            'pagination' => [
                'paginate'                  => true,
                'per_page'                  => 10,
                'allow_pagination_override' => true,
                'allow_per_page_override'   => true,
            ],
            'requests' => [
                'force_custom'   => false,
                'only_validated' => false,
            ],
        ],
        'api' => [
            'pagination' => [
                'per_page'                  => 25,
                'allow_pagination_override' => false,
            ],
        ],
    ],
];
```

### Custom configurations

A controller can select a named configuration to override default values:

```php
class ItemController extends CrudableController
{
    protected function getConfigName(): string
    {
        return 'api';
    }
}
```

Config resolution order: named configuration value → `default` configuration value → hardcoded fallback.

## Development

### Without Docker

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/pint
```

### With Laravel Sail (Docker)

The Docker image is built from the Laravel Sail PHP 8.5 runtime. Composer must be installed locally first so the Sail runtime files are available as the build
context.

```bash
composer install
```

Build and start the container:

```bash
./vendor/bin/sail build
./vendor/bin/sail up -d
```

Run tests and code style:

```bash
./vendor/bin/sail php vendor/bin/phpunit
./vendor/bin/sail php vendor/bin/pint
```

Open a shell inside the container:

```bash
./vendor/bin/sail shell
```

Stop the container:

```bash
./vendor/bin/sail down
```
