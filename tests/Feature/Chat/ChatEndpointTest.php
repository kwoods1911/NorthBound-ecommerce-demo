<?php

use App\Models\ChatConversation;
use App\Models\Product;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\Data\AiChatResponse;
use App\Services\Ai\Data\AiToolCall;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeChatProvider;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->provider = new FakeChatProvider;
    $this->app->instance(AiChatProvider::class, $this->provider);
});

it('validates that a message is present', function () {
    $this->postJson('/chat', [])->assertStatus(422)->assertJsonValidationErrors('message');
});

it('rejects an over-long message', function () {
    $this->postJson('/chat', ['message' => str_repeat('a', 1001)])
        ->assertStatus(422)->assertJsonValidationErrors('message');
});

it('answers a message and returns the assistant turn as JSON', function () {
    $this->provider->queue(new AiChatResponse('We sell suitcases and packing cubes.'));

    $this->postJson('/chat', ['message' => 'what do you sell?'])
        ->assertOk()
        ->assertJsonPath('message.role', 'assistant')
        ->assertJsonPath('message.content', 'We sell suitcases and packing cubes.')
        ->assertJsonPath('message.products', []);
});

it('renders tool results as product cards in the response', function () {
    Product::factory()->create(['name' => 'Weekender Bag']);

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('searchProducts-0', 'searchProducts', ['query' => 'weekender'])]),
        new AiChatResponse('Take a look at the Weekender Bag.'),
    );

    $this->postJson('/chat', ['message' => 'weekender bag?'])
        ->assertOk()
        ->assertJsonPath('message.products.0.name', 'Weekender Bag')
        ->assertJsonPath('message.products.0.url', '/products/'.Product::first()->slug);
});

it('keeps one conversation across requests in a session and replays it on history', function () {
    $this->provider->queue(
        new AiChatResponse('First answer.'),
        new AiChatResponse('Second answer.'),
    );

    $this->postJson('/chat', ['message' => 'first'])->assertOk();
    $this->postJson('/chat', ['message' => 'second'])->assertOk();

    expect(ChatConversation::count())->toBe(1);

    $this->getJson('/chat/history')
        ->assertOk()
        ->assertJsonCount(4, 'messages')
        ->assertJsonPath('messages.0.content', 'first')
        ->assertJsonPath('messages.1.content', 'First answer.')
        ->assertJsonPath('messages.3.content', 'Second answer.');
});

it('history omits internal tool turns', function () {
    Product::factory()->create();

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('searchProducts-0', 'searchProducts', ['query' => 'a'])]),
        new AiChatResponse('Done.'),
    );

    $this->postJson('/chat', ['message' => 'search'])->assertOk();

    $this->getJson('/chat/history')
        ->assertOk()
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.1.content', 'Done.');
});
