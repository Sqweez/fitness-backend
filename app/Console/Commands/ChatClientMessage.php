<?php

namespace App\Console\Commands;

use App\Http\Services\ChatService;
use App\Models\Client;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Local testing helper: sends a chat message as a client, exactly like the mobile app does.
 */
class ChatClientMessage extends Command
{
    protected $signature = 'chat:client-message
        {--client= : Client ID (default: random client that already has a chat)}
        {--text= : Message text (default: random phrase)}
        {--new : Use a random client without a chat to open a new one}';

    protected $description = 'Send a chat message as a client (local testing only)';

    private const PHRASES = [
        'Здравствуйте! Подскажите, как продлить абонемент?',
        'Вы сегодня до скольки работаете?',
        'Можно заморозить карту на неделю?',
        'Забыл в раздевалке полотенце',
        'Есть свободные тренеры на вечер?',
        'Сколько стоит безлимит на 3 месяца?',
        'Не прошло посещение по карте',
        'Спасибо, всё понравилось!',
        'Можно прийти с ребёнком?',
        'Ау, есть кто?',
    ];

    public function handle(ChatService $chats): int {
        if (app()->isProduction()) {
            $this->error('This command is for local testing only.');
            return self::FAILURE;
        }

        $client = $this->option('client')
            ? Client::query()->findOrFail($this->option('client'))
            : $this->randomClient((bool) $this->option('new'));
        $text = $this->option('text') ?: Arr::random(self::PHRASES);

        $message = $chats->sendClientMessage($client, $text, (string) Str::uuid());
        $this->info("Client #{$client->id} {$client->name} (club {$client->club_id}) → chat #{$message->chat_id}: {$text}");

        return self::SUCCESS;
    }

    private function randomClient(bool $new): Client {
        // Only clubs that have an admin or a boss, so the chat shows up in the admin panel
        $clubIds = DB::table('club_user')
            ->join('role_user', 'role_user.user_id', '=', 'club_user.user_id')
            ->whereIn('role_user.role_id', [Role::ROLE_BOSS, Role::ROLE_ADMIN])
            ->pluck('club_user.club_id');

        return Client::query()
            ->whereIn('club_id', $clubIds)
            ->when($new, fn (Builder $query) => $query->whereDoesntHave('chat'), fn (Builder $query) => $query->whereHas('chat'))
            ->inRandomOrder()
            ->firstOrFail();
    }
}
