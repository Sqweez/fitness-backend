<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\Pass;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Trinket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Exceptions\HttpResponseException;

class MobileClientService
{
    public function getProfile(Client $client): array {
        $name = preg_split('/\s+/u', trim($client->name));

        return [
            'first_name' => $name[1],
            'last_name' => $name[0],
        ];
    }

    public function getMemberships(Client $client): array {
        $programs = $this->membershipSales($client)->get()->pluck('salable');
        $priorities = ['active' => 0, 'pending_activation' => 1, 'frozen' => 2];
        $current = $programs->filter(function (ServiceSale $program) use ($priorities) {
            return isset($priorities[$program->membership_status]);
        })->sort(function (ServiceSale $first, ServiceSale $second) use ($priorities) {
            return ($priorities[$first->membership_status] <=> $priorities[$second->membership_status])
                ?: ($second->sale->id <=> $first->sale->id);
        })->values();

        return [
            'memberships' => $current,
            'last_membership' => $current->isEmpty() ? $programs->first() : null,
        ];
    }

    public function getMembershipHistory(Client $client, int $limit, int $cursor): array {
        $sales = $this->membershipSales($client)
            ->when($cursor > 0, function (Builder $query) use ($cursor) {
                $query->where('sales.id', '<', $cursor);
            })
            ->limit($limit + 1)->get();
        $items = $sales->take($limit)->values();

        return [
            'items' => $items->pluck('salable'),
            'next_cursor' => $sales->count() > $limit ? (int) $items->last()->id : null,
        ];
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

    private function membershipSales(Client $client): HasMany {
        return $client->sales()
            ->whereHasMorph('salable', [ServiceSale::class], function (Builder $query) {
                $query->whereHas('service', function (Builder $query) {
                    $query->withTrashed()->whereIn('service_type_id', [Service::TYPE_PROGRAM, Service::TYPE_UNLIMITED]);
                });
            })
            ->with([
                'salable.service' => function (BelongsTo $query) {
                    $query->withTrashed();
                },
                'salable.visits', 'salable.penalties', 'salable.active_stop', 'salable.sale',
            ])
            ->reorder()->orderByDesc('sales.id');
    }
}
