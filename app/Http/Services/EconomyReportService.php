<?php

namespace App\Http\Services;

use App\Http\Resources\Economy\AccountTopUp;
use App\Http\Resources\Economy\ClubGuests;
use App\Http\Resources\Economy\NewClients;
use App\Models\Client;
use App\Models\ClientReplenishment;
use App\Models\Sale;
use App\Models\ServiceSale;
use App\Models\Session;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EconomyReportService
{
    public function getTopUps(array $filters): array
    {
        $scope = ClientReplenishment::query()
            ->whereBetween('created_at', [$filters['start'], $filters['finish']])
            ->when($filters['club_id'], fn (Builder $q, $id) => $q->where('club_id', $id))
            ->when($filters['user_id'], fn (Builder $q, $id) => $q->where('user_id', $id));

        $totals = (clone $scope)->toBase()
            ->selectRaw('COALESCE(SUM(amount), 0) as amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_type = 1 THEN amount ELSE 0 END), 0) as cash')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_type = 2 THEN amount ELSE 0 END), 0) as cashless')
            ->first();

        $page = $scope
            ->when($filters['search'], fn (Builder $q, $search) => $q->whereIn('client_id', $this->clientIds($search)))
            ->with(['club:id,name', 'client:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

        return [
            'data' => AccountTopUp::collection($page->getCollection()),
            'meta' => $this->meta($page->total(), $filters),
            'totals' => [
                'amount' => (int) $totals->amount,
                'cash' => (int) $totals->cash,
                'cashless' => (int) $totals->cashless,
            ],
        ];
    }

    public function getPrograms(array $filters): array
    {
        $scope = Sale::query()
            ->where('sales.salable_type', ServiceSale::class)
            ->whereBetween('sales.created_at', [$filters['start'], $filters['finish']])
            ->when($filters['club_id'], fn (Builder $q, $id) => $q->where('sales.club_id', $id))
            ->when($filters['user_id'], fn (Builder $q, $id) => $q->where('sales.user_id', $id));

        $totals = $this->joinTransactions((clone $scope)->toBase())
            ->selectRaw('COALESCE(-SUM(transactions.amount), 0) as amount')
            ->first();

        // One row per purchase: sales created in the same second for the same client are merged
        $grouped = $this->joinTransactions(
            $scope->when($filters['search'], fn (Builder $q, $search) => $q->whereIn('sales.client_id', $this->clientIds($search)))
        )->groupBy('sales.client_id', 'sales.created_at');

        $total = DB::query()
            ->fromSub((clone $grouped)->toBase()->select('sales.client_id', 'sales.created_at'), 'purchases')
            ->count();

        $purchases = $grouped
            ->selectRaw('MIN(sales.id) as id, COALESCE(-SUM(transactions.amount), 0) as amount')
            ->orderByDesc('sales.created_at')
            ->orderByRaw('MIN(sales.id) desc')
            ->forPage($filters['page'], $filters['per_page'])
            ->get()
            ->pluck('amount', 'id');

        $sales = Sale::query()
            ->whereKey($purchases->keys())
            ->with(['client:id,name', 'user:id,name', 'club:id,name', 'salable.service'])
            ->get()
            ->keyBy('id');

        $rows = $purchases->map(function ($amount, $id) use ($sales) {
            $sale = $sales[$id];

            return [
                'id' => $sale->id,
                'name' => $sale->salable->service ? $sale->salable->service->name : 'Удаленная программа',
                'user' => $sale->user,
                'club' => $sale->club,
                'client' => $sale->client,
                'amount' => (int) $amount,
                'date' => format_datetime($sale->created_at),
            ];
        })->values();

        return [
            'data' => $rows,
            'meta' => $this->meta($total, $filters),
            'totals' => ['amount' => (int) $totals->amount],
        ];
    }

    public function getGuests(array $filters): array
    {
        $scope = Session::query()
            ->whereNotNull('finished_at')
            ->whereBetween('created_at', [$filters['start'], $filters['finish']])
            ->when($filters['club_id'], fn (Builder $q, $id) => $q->where('club_id', $id))
            ->when($filters['user_id'], fn (Builder $q, $id) => $q->where('start_user_id', $id));

        $totals = (clone $scope)->toBase()
            ->selectRaw('COUNT(*) as visits, COUNT(DISTINCT client_id) as unique_visitors')
            ->first();

        $page = $scope
            ->when($filters['search'], fn (Builder $q, $search) => $q->whereIn('client_id', $this->clientIds($search)))
            ->with(['client:id,name', 'start_user:id,name', 'finish_user:id,name', 'club:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

        return [
            'data' => ClubGuests::collection($page->getCollection()),
            'meta' => $this->meta($page->total(), $filters),
            'totals' => [
                'visits' => (int) $totals->visits,
                'unique_visitors' => (int) $totals->unique_visitors,
            ],
        ];
    }

    public function getNewClients(array $filters): array
    {
        $page = Client::query()
            ->withoutGlobalScope('order')
            ->whereBetween('created_at', [$filters['start'], $filters['finish']])
            ->when($filters['club_id'], fn (Builder $q, $id) => $q->where('club_id', $id))
            ->when($filters['user_id'], fn (Builder $q, $id) => $q->where('user_id', $id))
            ->when($filters['search'], fn (Builder $q, $search) => $q->where('name', 'like', prepare_search_string($search)))
            ->with(['club:id,name', 'registrar:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page']);

        return [
            'data' => NewClients::collection($page->getCollection()),
            'meta' => $this->meta($page->total(), $filters),
            'totals' => [],
        ];
    }

    private function clientIds(string $search)
    {
        return Client::withTrashed()
            ->withoutGlobalScope('order')
            ->select('id')
            ->where('name', 'like', prepare_search_string($search));
    }

    private function joinTransactions($query)
    {
        return $query->leftJoin('transactions', function ($join) {
            $join->on('transactions.transactional_id', '=', 'sales.id')
                ->where('transactions.transactional_type', '=', Sale::class);
        });
    }

    private function meta(int $total, array $filters): array
    {
        return [
            'total' => $total,
            'page' => $filters['page'],
            'per_page' => $filters['per_page'],
        ];
    }
}
