<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Enums;

enum EventAction: string
{
    case Created = 'Created';
    case Updated = 'Updated';
    case Deleted = 'Deleted';
}
