<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class UpdateTest extends CrudTestCase
{
    private function sendRequest(Item|int $item, array $data = []): TestResponse
    {
        return $this->putJson(route('items.update', $item), $data);
    }

    #[Test]
    public function modifies_item(): void
    {
        $item = Item::factory()->create(['name' => 'Old Name']);

        $this->sendRequest($item, ['name' => 'New Name'])
            ->assertOk()
            ->assertExactJson(['data' => new ItemResource($item->fresh())->resolve()]);

        $this->assertSame('New Name', $item->fresh()->name);
    }

    #[Test]
    public function returns_404_for_missing_item(): void
    {
        $this->sendRequest(999)
            ->assertNotFound();
    }

    #[Test]
    public function passes_all_fields_when_only_validated_is_disabled(): void
    {
        $item = Item::factory()->create();

        $this->sendRequest($item, ['name' => 'New Name', 'secret' => 'sensitive'])->assertOk();

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'New Name', 'secret' => 'sensitive']);
    }

    #[Test]
    public function returns_422_when_form_request_validation_fails(): void
    {
        $item = Item::factory()->create();

        $this->sendRequest($item)
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }
}
