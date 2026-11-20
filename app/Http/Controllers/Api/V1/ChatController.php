<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ChatPageRequest;
use App\Http\Requests\Chat\ReadMessagesRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Http\Resources\Chat\ChatMessagesResource;
use App\Http\Resources\Chat\ChatsResource;
use App\Http\Services\ChatService;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChatController extends Controller
{
    private ChatService $service;

    public function __construct(ChatService $service) {
        $this->service = $service;
    }

    public function index(ChatPageRequest $request): ChatsResource {
        $data = $request->validated();
        return ChatsResource::make($this->service->getStaffChats($request->user(), (int) $data['limit'], (int) $data['cursor']));
    }

    public function unread(Request $request): JsonResponse {
        return response()->json(['data' => $this->service->getStaffUnreadSummary($request->user())]);
    }

    public function messages(ChatPageRequest $request, Chat $chat): ChatMessagesResource {
        $data = $request->validated();
        return ChatMessagesResource::make($this->service->getMessages($chat, (int) $data['limit'], (int) $data['cursor']));
    }

    public function send(SendMessageRequest $request, Chat $chat): ChatMessageResource {
        $data = $request->validated();
        return ChatMessageResource::make($this->service->sendStaffMessage($chat, $request->user(), $data['text'], $data['client_message_id']));
    }

    public function read(ReadMessagesRequest $request, Chat $chat): Response {
        $this->service->markReadByStaff($chat, (int) $request->validated()['last_read_message_id']);
        return response()->noContent();
    }
}
