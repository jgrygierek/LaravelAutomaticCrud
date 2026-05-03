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
        - [Filtering with SearchableInterface](#filtering-with-searchableinterface)
    - [Requests](#requests)
        - [Namespace override](#namespace-override-1)
        - [Request class convention](#request-class-convention)
        - [Force custom requests](#force-custom-requests)
        - [Only validated fields](#only-validated-fields)
    - [Resources](#resources)
        - [Namespace override](#namespace-override-2)
        - [Resource class convention](#resource-class-convention)
        - [Resource class override](#resource-class-override)
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

`searchFilters()` can also return a keyed array to provide different filter sets per named configuration. The key matches the controller's `$configName`; `default` is used as a fallback when no matching key is found:

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

A controller using `protected string $configName = 'api'` will get `[ActiveFilter::class, NameFilter::class]`, while all others fall back to `[NameFilter::class]`.

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
| destroy | `{request_namespace}\{Models}\Destroy{Model}Request` | `App\Http\Requests\Items\DestroyItemRequest` |

Form requests are optional by default — if the class does not exist, the standard `request()` is used.

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
    protected string $configName = 'api';
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
