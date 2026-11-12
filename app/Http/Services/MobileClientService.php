<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\Pass;
use App\Models\Sale;
use App\Models\Trinket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;

class MobileClientService
{
    public function getProfile(Client $client): array {
        $name = preg_split('/\s+/u', trim($client->name));

        return [
            'first_name' => $name[1],
            'last_name' => $name[0],
        ];
    }

    public function getMemberships(Client $client): Collection {
        return $client->programs()
            ->with(['salable.service', 'salable.visits', 'salable.penalties', 'salable.active_stop'])
            ->orderByDesc('id')
            ->get()
            ->filter(function (Sale $sale) {
                $program = $sale->salable;
                return (!$program->is_expired || $program->is_stopped)
                    && ($program->is_unlimited || $program->remaining_visits > 0);
            })
            ->pluck('salable')
            ->values();
    }

    public function getTrinket(Client $client): ?Trinket {
        return Trinket::query()->whereHas('sessions', function (Builder $query) use ($client) {
            $query->where('client_id', $client->id)->whereNull('finished_at');
        })->first();
    }

    public function getPass(Client $client): Pass {
        $pass = $client->pass;
        if ($pass === null) {
            throw new HttpResponseException(response()->json([
                'error' => ['code' => 'PASS_UNAVAILABLE', 'message' => 'Карта клиента не выдана'],
            ], 403));
        }

        return $pass;
    }

    public function getVisits(Client $client, int $limit, int $cursor): array {
        $visits = $client->sessions()
            ->when($cursor > 0, function (Builder $query) use ($cursor) {
                $query->where('id', '<', $cursor);
            })
            ->limit($limit + 1)
            ->get(['id', 'created_at']);
        $items = $visits->take($limit)->values();

        return [
            'items' => $items,
            'next_cursor' => $visits->count() > $limit ? (int) $items->last()->id : null,
        ];
    }
}
