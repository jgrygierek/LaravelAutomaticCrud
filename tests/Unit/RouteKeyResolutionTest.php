<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Unit;

use Closure;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\ItemController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RouteKeyResolutionTest extends TestCase
{
    #[Test]
    public function uses_model_route_key_name_when_there_is_no_current_route(): void
    {
        $controller = new ItemController();

        $result = Closure::bind(fn () => $this->resolveRouteKeyColumn(Item::query()), $controller, $controller::class)();

        $this->assertSame('id', $result);
    }
}
