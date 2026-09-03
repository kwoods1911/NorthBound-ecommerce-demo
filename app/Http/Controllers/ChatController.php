<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    /** Current conversation history, for hydrating the panel on load. */
    public function history(): JsonResponse
    {
        $conversation = $this->chat->conversationForCurrentSession();

        return response()->json([
            'messages' => ChatMessageResource::collection(
                $conversation->messages()->displayable()->get()
            ),
        ]);
    }

    public function store(SendChatMessageRequest $request): JsonResponse
    {
        $conversation = $this->chat->conversationForCurrentSession();

        $reply = $this->chat->send($conversation, $request->userMessage());

        return response()->json([
            'message' => new ChatMessageResource($reply),
        ]);
    }
}
