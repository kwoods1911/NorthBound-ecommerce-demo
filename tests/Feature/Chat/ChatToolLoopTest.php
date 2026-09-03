<?php

use App\Enums\ChatRole;
use App\Models\ChatConversation;
use App\Models\Product;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\Data\AiChatResponse;
use App\Services\Ai\Data\AiToolCall;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\ChatService;
use Tests\Fakes\FakeChatProvider;

beforeEach(function () {
    $this->provider = new FakeChatProvider;
    $this->app->instance(AiChatProvider::class, $this->provider);
    $this->chat = $this->app->make(ChatService::class);
});

function conversation(): ChatConversation
{
    return ChatConversation::factory()->create();
}

it('runs a searchProducts tool call and returns product cards', function () {
    $match = Product::factory()->create(['name' => 'Aluminium Carry-On', 'sales_count' => 50]);
    Product::factory()->create(['name' => 'Leather Wallet']);

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('searchProducts-0', 'searchProducts', ['query' => 'carry-on'])]),
        new AiChatResponse('The Aluminium Carry-On is a great pick.'),
    );

    $reply = $this->chat->send(conversation(), 'show me a carry-on');

    expect($reply->role)->toBe(ChatRole::Assistant)
        ->and($reply->content)->toBe('The Aluminium Carry-On is a great pick.')
        ->and($reply->tool_payload['products'])->toHaveCount(1)
        ->and($reply->tool_payload['products'][0]['name'])->toBe('Aluminium Carry-On')
        ->and($this->provider->callCount())->toBe(2);
});

it('persists the assistant tool-call turn and the tool result', function () {
    Product::factory()->create(['name' => 'Packing Cube Set']);

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('searchProducts-0', 'searchProducts', ['query' => 'packing'])]),
        new AiChatResponse('Here you go.'),
    );

    $convo = conversation();
    $this->chat->send($convo, 'packing cubes?');

    $roles = $convo->messages()->pluck('role')->map->value->all();
    expect($roles)->toBe(['user', 'assistant', 'tool', 'assistant']);

    $toolMessage = $convo->messages()->where('role', ChatRole::Tool)->first();
    expect($toolMessage->tool_name)->toBe('searchProducts')
        ->and($toolMessage->tool_payload['count'])->toBe(1);
});

it('replays the tool call id and thought signature on the follow-up request', function () {
    Product::factory()->create(['name' => 'Cabin Bag']);

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('call_42', 'searchProducts', ['query' => 'cabin'], 'sig-777')]),
        new AiChatResponse('The Cabin Bag fits the overhead bin.'),
    );

    $this->chat->send(conversation(), 'cabin bag?');

    $followUp = $this->provider->lastRequest();
    $assistantTurn = collect($followUp->messages)->firstWhere('role', ChatRole::Assistant);
    $toolTurn = collect($followUp->messages)->firstWhere('role', ChatRole::Tool);

    expect($assistantTurn->toolCalls[0]->id)->toBe('call_42')
        ->and($assistantTurn->toolCalls[0]->signature)->toBe('sig-777')
        ->and($toolTurn->toolCallId)->toBe('call_42');
});

it('dispatches checkStock through to the product', function () {
    $product = Product::factory()->create(['stock_quantity' => 7]);

    $this->provider->queue(
        new AiChatResponse(null, [new AiToolCall('checkStock-0', 'checkStock', ['productId' => $product->id])]),
        new AiChatResponse('There are 7 in stock.'),
    );

    $convo = conversation();
    $this->chat->send($convo, 'is it in stock?');

    $toolMessage = $convo->messages()->where('role', ChatRole::Tool)->first();
    expect($toolMessage->tool_payload)->toMatchArray([
        'found' => true,
        'stock_quantity' => 7,
        'in_stock' => true,
    ]);
});

it('stops after the max tool iterations without looping forever', function () {
    $this->provider->queue(
        ...array_fill(0, 8, new AiChatResponse(null, [new AiToolCall('searchProducts-0', 'searchProducts', ['query' => 'x'])])),
    );

    $reply = $this->chat->send(conversation(), 'loop');

    expect($this->provider->callCount())->toBe(4)
        ->and($reply->role)->toBe(ChatRole::Assistant)
        ->and($reply->content)->not->toBeEmpty();
});

it('returns a friendly message on a rate limit and does not persist a broken reply', function () {
    $this->provider->queue(new AiRateLimitException('429'));

    $convo = conversation();
    $reply = $this->chat->send($convo, 'hello');

    expect($reply->content)->toContain('moment')
        ->and($convo->messages()->where('role', ChatRole::Assistant)->count())->toBe(1);
});

it('advertises exactly the two allowed tools to the provider', function () {
    $this->provider->queue(new AiChatResponse('hi'));

    $this->chat->send(conversation(), 'hi');

    $names = collect($this->provider->lastRequest()->tools)->pluck('name')->sort()->values()->all();
    expect($names)->toBe(['checkStock', 'searchProducts']);
});
