<?php

namespace App\Http\Services;

use App\Events\Chat\ChatMessageSent;
use App\Events\Chat\ChatRead;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function getMessages(?Chat $chat, int $limit, int $cursor): array {
        if ($chat === null) {
            return ['chat' => null, 'items' => collect(), 'next_cursor' => null];
        }

        $messages = $chat->messages()->with('user')
            ->when($cursor > 0, function (Builder $query) use ($cursor) {
                $query->where('id', '<', $cursor);
            })
            ->orderByDesc('id')->limit($limit + 1)->get();
        $items = $messages->take($limit);

        return [
            'chat' => $chat,
            'items' => $items->reverse()->values(),
            'next_cursor' => $messages->count() > $limit ? $items->last()->id : null,
        ];
    }

    /**
     * @return ChatMessage Existing message when the client_message_id was already used
     */
    public function sendClientMessage(Client $client, string $text, string $clientMessageId): ChatMessage {
        $chat = Chat::query()->createOrFirst(['client_id' => $client->id]);
        return $this->send($chat, null, $text, $clientMessageId);
    }

    public function sendStaffMessage(Chat $chat, User $user, string $text, string $clientMessageId): ChatMessage {
        return $this->send($chat, $user, $text, $clientMessageId);
    }

    public function markReadByClient(?Chat $chat, int $messageId): void {
        $this->markRead($chat, $messageId, 'client_last_read_message_id');
    }

    public function markReadByStaff(Chat $chat, int $messageId): void {
        $this->markRead($chat, $messageId, 'staff_last_read_message_id');
    }

    public function getClientUnreadCount(?Chat $chat): int {
        if ($chat === null) {
            return 0;
        }

        return $chat->messages()->whereNotNull('user_id')
            ->where('id', '>', (int) $chat->client_last_read_message_id)
            ->count();
    }

    public function getStaffUnreadCount(Chat $chat): int {
        return $chat->messages()->whereNull('user_id')
            ->where('id', '>', (int) $chat->staff_last_read_message_id)
            ->count();
    }

    public function getStaffChats(User $user, int $limit, int $cursor): array {
        $chats = Chat::query()
            ->whereNotNull('last_message_id')
            ->whereHas('client', function (Builder $query) use ($user) {
                $query->whereIn('club_id', $this->staffClubIds($user));
            })
            ->when($cursor > 0, function (Builder $query) use ($cursor) {
                $query->where('last_message_id', '<', $cursor);
            })
            ->with(['client', 'lastMessage.user'])
            ->withCount(['messages as unread_count' => function (Builder $query) {
                $query->whereNull('user_id')
                    ->whereRaw('chat_messages.id > COALESCE(chats.staff_last_read_message_id, 0)');
            }])
            ->orderByDesc('last_message_id')->limit($limit + 1)->get();
        $items = $chats->take($limit)->values();

        return [
            'items' => $items,
            'next_cursor' => $chats->count() > $limit ? $items->last()->last_message_id : null,
        ];
    }

    public function getStaffUnreadSummary(User $user): array {
        // Staff replies move the staff pointer, so a chat has unread client messages exactly when
        // its last message is newer than the pointer; only those few chats are counted.
        $counts = Chat::query()
            ->whereRaw('chats.last_message_id > COALESCE(chats.staff_last_read_message_id, 0)')
            ->whereHas('client', function (Builder $query) use ($user) {
                $query->whereIn('club_id', $this->staffClubIds($user));
            })
            ->withCount(['messages as unread_count' => function (Builder $query) {
                $query->whereNull('user_id')
                    ->whereRaw('chat_messages.id > COALESCE(chats.staff_last_read_message_id, 0)');
            }])
            ->get()->pluck('unread_count');

        return [
            'chats' => $counts->count(),
            'messages' => $counts->sum(),
        ];
    }

    private function send(Chat $chat, ?User $user, string $text, string $clientMessageId): ChatMessage {
        return DB::transaction(function () use ($chat, $user, $text, $clientMessageId) {
            // Row lock serializes concurrent sends so a retried client_message_id never creates a duplicate
            $chat = Chat::query()->lockForUpdate()->findOrFail($chat->id);
            $existing = $chat->messages()->where('client_message_id', $clientMessageId)->first();
            if ($existing !== null) {
                // The id belongs to another author's message: not a retry of this request
                if ($existing->user_id !== $user?->id) {
                    throw ValidationException::withMessages(['client_message_id' => 'Идентификатор сообщения уже использован']);
                }
                return $existing->load('user');
            }

            $message = $chat->messages()->create([
                'user_id' => $user?->id,
                'text' => $text,
                'client_message_id' => $clientMessageId,
            ]);
            // Replying means the author has seen everything before their own message
            $chat->last_message_id = $message->id;
            $chat->{$user ? 'staff_last_read_message_id' : 'client_last_read_message_id'} = $message->id;
            $chat->save();

            ChatMessageSent::dispatch($message);

            return $message->load('user');
        });
    }

    private function markRead(?Chat $chat, int $messageId, string $field): void {
        if ($chat === null || !$chat->messages()->whereKey($messageId)->exists()) {
            throw ValidationException::withMessages(['last_read_message_id' => 'Сообщение не найдено в диалоге']);
        }

        DB::transaction(function () use ($chat, $messageId, $field) {
            $chat = Chat::query()->lockForUpdate()->findOrFail($chat->id);
            if ((int) $chat->{$field} >= $messageId) {
                return;
            }

            $chat->{$field} = $messageId;
            $chat->save();

            ChatRead::dispatch($chat);
        });
    }

    private function staffClubIds(User $user): array {
        return $user->clubs()->pluck('clubs.id')->all();
    }
}
