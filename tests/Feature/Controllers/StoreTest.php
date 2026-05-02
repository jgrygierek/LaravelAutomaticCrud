<?php

declare(strict_types=1);

namespace JG\LaravelAutomaticCrud\Tests\Feature\Controllers;

use Illuminate\Testing\TestResponse;
use JG\LaravelAutomaticCrud\Tests\Feature\CrudTestCase;
use JG\LaravelAutomaticCrud\Tests\Support\Models\Item;
use JG\LaravelAutomaticCrud\Tests\Support\Resources\ItemResource;
use PHPUnit\Framework\Attributes\Test;

final class StoreTest extends CrudTestCase
{
    private function sendRequest(array $data = []): TestResponse
    {
        return $this->postJson(route('items.store'), $data);
    }

    #[Test]
    public function creates_item_and_returns_201(): void
    {
        $response = $this->sendRequest(['name' => 'New Item'])->assertCreated();

        $item = Item::firstWhere('name', 'New Item');

        $response->assertExactJson(['data' => new ItemResource($item)->resolve()]);
        $this->assertDatabaseHas('items', ['name' => 'New Item']);
    }

    #[Test]
    public function passes_all_fields_when_only_validated_is_disabled(): void
    {
        $this->sendRequest(['name' => 'New Item', 'secret' => 'sensitive'])->assertCreated();

        $this->assertDatabaseHas('items', ['name' => 'New Item', 'secret' => 'sensitive']);
    }

    #[Test]
    public function ignores_fields_not_present_in_form_request_rules_when_only_validated_is_enabled(): void
    {
        config()->set('automatic-crud.defaults.requests.only_validated', true);
        config()->set('automatic-crud.defaults.requests.force_custom', true);

        $this->sendRequest(['name' => 'New Item', 'secret' => 'sensitive'])->assertCreated();

        $this->assertDatabaseMissing('items', ['secret' => 'sensitive']);
        $this->assertDatabaseHas('items', ['name' => 'New Item', 'secret' => null]);
    }

    #[Test]
    public function returns_422_when_form_request_validation_fails(): void
    {
        $this->sendRequest()
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }
}
