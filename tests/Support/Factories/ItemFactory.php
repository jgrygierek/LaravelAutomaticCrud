<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Support\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }
}
