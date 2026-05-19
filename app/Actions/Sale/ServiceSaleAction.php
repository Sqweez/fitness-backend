<?php

namespace App\Actions\Sale;

use App\Http\Services\TransactionService;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ServiceSaleAction {

    public function handle(array $payload) {
        return DB::transaction(function () use ($payload) {
            $service = Service::find($payload['service_id']);
            $client = Client::find($payload['client_id']);
            $repeatCount = $payload['count'];
            for ($i = 0; $i < $repeatCount; $i++) {
                $serviceSalePayload = $this->_mapServiceSalePayload($service, $payload);
                $serviceSale = ServiceSale::create($serviceSalePayload);
                $salePayload = $this->_mapSalePayload($payload);
                $sale = $serviceSale->sale()->create($salePayload);
                $transactionPayload = $this->_mapTransactionPayload($service, $payload, $i);
                $transaction = $sale->transaction()->create($transactionPayload);
                // Инкремент т.к. значение транзакции уже отрицательное
                $client->increment('balance', $transaction->amount);
            }
            if ($service->service_type_id === Service::TYPE_SOLARIUM) {
                $client->update(['cached_solarium_total' => $client->cached_solarium_total + $service->validity_minutes * $payload['count']]);
                $client->refresh();
            }
            return $client;
        });
    }

    public function _mapServiceSalePayload(Service $service, $payload): array {
        return [
            'service_id' => $service->id,
            'entries_count' => $service->entries_count ?? null,
            'minutes_remaining' => ($service->validity_minutes && $service->validity_minutes > 0) ? $service->validity_minutes : null,
            'active_until' => null,
            'user_id' => $payload['user_id'],
            'self_name' => $payload['self_name'],
        ];
    }

    public function _mapTransactionPayload(Service $service, $payload, $iterationStep = 0): array {

        $serviceName = $service->name;

        if ($payload['is_prolongation']) {
            $serviceName = "пролонгацию " . $serviceName;
        }

        $description = sprintf("Списание средств за %s", $serviceName);

        $amount = ceil($payload['amount']) * -1;

        if ($iterationStep > 0) {
            $amount = 0;
        }

        return [
            'user_id' => $payload['user_id'],
            'client_id' => $payload['client_id'],
            'amount' => $amount,
            'club_id' => $payload['club_id'],
            'description' => $description,
        ];
    }

    public function _mapSalePayload($payload): array {
        return [
            'client_id' => $payload['client_id'],
            'user_id' => $payload['user_id'],
            'club_id' => $payload['club_id'],
        ];
    }
}
