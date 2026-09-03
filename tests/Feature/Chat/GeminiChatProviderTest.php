<?php

use App\Services\Ai\Data\AiChatRequest;
use App\Services\Ai\Data\AiMessage;
use App\Services\Ai\Data\AiToolCall;
use App\Services\Ai\Data\ToolDefinition;
use App\Services\Ai\Exceptions\AiChatException;
use App\Services\Ai\Exceptions\AiRateLimitException;
use App\Services\Ai\GeminiChatProvider;
use Illuminate\Support\Facades\Http;

const GEMINI_MODEL = 'gemini-flash-lite-latest';

function gemini(int $retries = 0): GeminiChatProvider
{
    return new GeminiChatProvider('test-key', GEMINI_MODEL, 'https://gemini.test/v1beta', retries: $retries, retryBackoffMs: 0);
}

function geminiUrl(): string
{
    return 'https://gemini.test/v1beta/models/'.GEMINI_MODEL.':generateContent';
}

it('sends the system prompt, transcript and tool declarations', function () {
    Http::fake([geminiUrl() => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => 'Hi there.']]], 'finishReason' => 'STOP']],
    ])]);

    $response = gemini()->chat(new AiChatRequest(
        systemPrompt: 'You are a store assistant.',
        messages: [
            AiMessage::user('hello'),
            AiMessage::assistant(null, [new AiToolCall('call_1', 'searchProducts', ['query' => 'bag'], 'sig-abc')]),
            AiMessage::tool('searchProducts', ['count' => 0, 'products' => []], 'call_1'),
        ],
        tools: [new ToolDefinition('searchProducts', 'Search the catalogue.', ['type' => 'object'])],
    ));

    expect($response->text)->toBe('Hi there.');

    Http::assertSent(function ($request) {
        $body = $request->data();
        $modelParts = $body['contents'][1]['parts'][0];

        return $request->hasHeader('x-goog-api-key', 'test-key')
            && $body['systemInstruction']['parts'][0]['text'] === 'You are a store assistant.'
            && $body['contents'][0] === ['role' => 'user', 'parts' => [['text' => 'hello']]]
            && $body['contents'][1]['role'] === 'model'
            && $modelParts['functionCall']['name'] === 'searchProducts'
            && $modelParts['functionCall']['id'] === 'call_1'
            && $modelParts['thoughtSignature'] === 'sig-abc'
            && $body['contents'][2]['parts'][0]['functionResponse']['id'] === 'call_1'
            && $body['contents'][2]['parts'][0]['functionResponse']['name'] === 'searchProducts'
            && $body['tools'][0]['functionDeclarations'][0]['name'] === 'searchProducts';
    });
});

it('parses a function call, its id and thought signature out of the response', function () {
    Http::fake([geminiUrl() => Http::response([
        'candidates' => [['content' => ['parts' => [[
            'functionCall' => ['name' => 'checkStock', 'args' => ['productId' => 12], 'id' => 'call_99'],
            'thoughtSignature' => 'sig-xyz',
        ]]]]],
    ])]);

    $response = gemini()->chat(new AiChatRequest('sys', [AiMessage::user('stock?')], []));

    expect($response->hasToolCalls())->toBeTrue()
        ->and($response->toolCalls[0]->name)->toBe('checkStock')
        ->and($response->toolCalls[0]->id)->toBe('call_99')
        ->and($response->toolCalls[0]->signature)->toBe('sig-xyz')
        ->and($response->toolCalls[0]->arguments)->toBe(['productId' => 12])
        ->and($response->text)->toBeNull();
});

it('maps HTTP 429 to AiRateLimitException', function () {
    Http::fake([geminiUrl() => Http::response(['error' => ['message' => 'quota']], 429)]);

    gemini()->chat(new AiChatRequest('sys', [AiMessage::user('hi')], []));
})->throws(AiRateLimitException::class);

it('surfaces the API error message on other failures', function () {
    Http::fake([geminiUrl() => Http::response(['error' => ['message' => 'bad model']], 404)]);

    gemini()->chat(new AiChatRequest('sys', [AiMessage::user('hi')], []));
})->throws(AiChatException::class, 'bad model');

it('retries a transient 503 then succeeds', function () {
    Http::fakeSequence(geminiUrl())
        ->push(['error' => ['message' => 'busy']], 503)
        ->push(['candidates' => [['content' => ['parts' => [['text' => 'Recovered.']]]]]], 200);

    $response = gemini(retries: 2)->chat(new AiChatRequest('sys', [AiMessage::user('hi')], []));

    expect($response->text)->toBe('Recovered.');
    Http::assertSentCount(2);
});
