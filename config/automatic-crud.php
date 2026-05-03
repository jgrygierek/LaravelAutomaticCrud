<?php

declare(strict_types=1);

return [
    'configs' => [
        'default' => [
            'namespaces' => [
                'request' => 'App\Http\Requests',
                'model' => 'App\Models',
                'resource' => 'App\Http\Resources',
            ],
            'pagination' => [
                'paginate' => true,
                'per_page' => 10,
                'allow_pagination_override' => true,
                'allow_per_page_override' => true,
            ],
            'requests' => [
                'force_custom' => false,
                'only_validated' => false,
            ],
        ],
    ],
];
