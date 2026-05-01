<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Http\Interfaces;

interface SearchableInterface
{
    public function searchFilters(): array;
}
