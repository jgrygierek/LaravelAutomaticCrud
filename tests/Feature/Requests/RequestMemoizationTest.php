<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Controllers\RequestMemoizationController;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Requests\MemoizationHooks\Items\UpdateItemRequest;
use PHPUnit\Framework\Attributes\Test;

final class RequestMemoizationTest extends CrudTestCase
{
    protected function defineRoutes($router): void
    {
        $router->apiResource('memoized-request-items', RequestMemoizationController::class)->only(['update']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        UpdateItemRequest::$rulesCalls = 0;
    }

    #[Test]
    public function form_request_is_validated_only_once_when_apply_request_is_called_multiple_times(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);

        $this->putJson(route('memoized-request-items.update', $item), ['name' => 'New Name'])
            ->assertOk();

        $this->assertSame(1, UpdateItemRequest::$rulesCalls);
        $this->assertSame('New Name', $item->fresh()->name);
    }
}
