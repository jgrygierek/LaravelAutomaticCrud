<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Iterator;
use JG\LaravelAutomaticCrud\Helpers\QueryHelper;
use RuntimeException;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

trait ExportTrait
{
    /**
     * @var class-string<JsonResource>|null
     */
    private ?string $resolvedResourceExportClass = null;

    abstract protected function getModelClass(): string;

    abstract protected function getConfig(string $key): mixed;

    abstract protected function applyRequest(): Request;

    abstract protected function buildQuery(Request $request): Builder;

    abstract protected function getResourceClassByConvention(string $suffix): string;

    abstract protected function resolveResourceClass(string $suffix): ?string;

    /**
     * @return array<int, string>
     */
    abstract protected function getIndexSortableColumns(): array;

    public function export(): StreamedResponse
    {
        $this->getResourceExportClass();
        $request = $this->applyRequest();
        $models = $this->lazyExportModels($this->buildQuery($request))->getIterator();
        $firstRow = $models->valid() ? $this->getExportRow($models->current(), $request) : null;

        return response()->streamDownload(
            fn () => $this->writeCsv($models, $firstRow, $request),
            $this->getExportFileName(),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @return class-string<JsonResource>
     */
    protected function getResourceExportClass(): string
    {
        return $this->resolvedResourceExportClass ??= $this->resolveResourceClass('ExportResource')
            ?? throw new RuntimeException(sprintf('Export resource class [%s] not found.', $this->getResourceClassByConvention('ExportResource')));
    }

    /**
     * @return array<int, string>
     */
    protected function getExportSortableColumns(): array
    {
        return $this->getIndexSortableColumns();
    }

    protected function getExportFileName(): string
    {
        return Str::plural(Str::snake(class_basename($this->getModelClass()))) . '.csv';
    }

    protected function getExportChunkSize(): int
    {
        return max(1, (int) $this->getConfig('export.chunk_size'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getExportRow(Model $model, Request $request): array
    {
        $resource = $this->getResourceExportClass();

        return new $resource($model)->resolve($request);
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @return list<int|string>|array<string, string>
     */
    protected function getExportHeader(array $row): array
    {
        return array_keys($row);
    }

    private function lazyExportModels(Builder $query): LazyCollection
    {
        $chunkSize = $this->getExportChunkSize();
        $keyName = $query->getModel()->getKeyName();
        $isSorted = QueryHelper::isSorted($query);

        return match (true) {
            $keyName === null => $isSorted ? $query->lazy($chunkSize) : $query->cursor(),
            !$isSorted => QueryHelper::groupWheres($query)->lazyById($chunkSize, $query->qualifyColumn($keyName), $keyName),
            default => $query->orderBy($query->qualifyColumn($keyName))->lazy($chunkSize),
        };
    }

    /**
     * @param  Iterator<int, Model>  $models
     * @param  array<int|string, mixed>|null  $firstRow
     */
    private function writeCsv(Iterator $models, ?array $firstRow, Request $request): void
    {
        $handle = fopen('php://output', 'wb');
        $columns = $this->writeExportHeader($handle, $firstRow ?? []);

        if ($firstRow !== null) {
            $this->writeExportRow($handle, $columns, $firstRow);

            for ($models->next(); $models->valid(); $models->next()) {
                $this->writeExportRow($handle, $columns, $this->getExportRow($models->current(), $request));
            }
        }

        fclose($handle);
    }

    /**
     * @param  resource  $handle
     * @param  array<int|string, int|string>  $columns
     * @param  array<int|string, mixed>  $row
     */
    private function writeExportRow($handle, array $columns, array $row): void
    {
        fputcsv($handle, array_map(fn (int|string $key) => $this->normalizeExportValue($row[$key] ?? null), array_keys($columns)), escape: '', eol: "\r\n");
    }

    /**
     * @param  resource  $handle
     * @param  array<int|string, mixed>  $row
     * @return array<int|string, int|string>
     */
    private function writeExportHeader($handle, array $row): array
    {
        $header = $this->getExportHeader($row);
        $columns = array_is_list($header) ? array_combine($header, $header) : $header;

        if ($columns !== []) {
            fputcsv($handle, $columns, escape: '', eol: "\r\n");
        }

        return $columns;
    }

    private function normalizeExportValue(mixed $value): string|int|float|null
    {
        return match (true) {
            is_bool($value) => (int) $value,
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof Stringable => (string) $value,
            is_array($value), is_object($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            default => $value,
        };
    }
}
