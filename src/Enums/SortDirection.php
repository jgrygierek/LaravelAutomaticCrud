<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Enums;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
