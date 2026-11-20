<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatPageRequest;
use App\Http\Requests\Chat\ReadMessagesRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\Mobile\Chat\ChatMessageResource;
use App\Http\Resources\Mobile\Chat\ChatMessagesResource;
use App\Http\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChatController extends Controller
{
    private ChatService $service;

    public function __construct(ChatService $service) {
        $this->service = $service;
    }

    public function messages(ChatPageRequest $request): ChatMessagesResource {
        $data = $request->validated();
        $chat = $request->user('mobile')->chat;
        return ChatMessagesResource::make($this->service->getMessages($chat, (int) $data['limit'], (int) $data['cursor']));
    }

    public function send(SendMessageRequest $request): ChatMessageResource {
        $data = $request->validated();
        return ChatMessageResource::make($this->service->sendClientMessage($request->user('mobile'), $data['text'], $data['client_message_id']));
    }

    public function unread(Request $request): JsonResponse {
        return response()->json(['data' => ['count' => $this->service->getClientUnreadCount($request->user('mobile')->chat)]]);
    }

    public function read(ReadMessagesRequest $request): Response {
        $this->service->markReadByClient($request->user('mobile')->chat, (int) $request->validated()['last_read_message_id']);
        return response()->noContent();
    }
}
