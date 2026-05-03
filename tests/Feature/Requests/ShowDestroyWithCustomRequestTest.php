<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Requests;

use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use PHPUnit\Framework\Attributes\Test;

final class ShowDestroyWithCustomRequestTest extends CrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('automatic-crud.configs.default.requests.force_custom', true);
    }

    #[Test]
    public function show_succeeds_when_request_class_exists(): void
    {
        $item = Item::create(['name' => 'Test']);

        $this->getJson(route('items.show', $item))
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }

    #[Test]
    public function destroy_succeeds_when_request_class_exists(): void
    {
        $item = Item::create(['name' => 'Test']);

        $this->deleteJson(route('items.destroy', $item))
            ->assertNoContent();

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }
}
