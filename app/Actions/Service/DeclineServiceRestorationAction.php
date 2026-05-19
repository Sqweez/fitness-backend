<?php

namespace App\Actions\Service;

use App\Models\RestoredService;

class DeclineServiceRestorationAction {

    public function handle(RestoredService $restored) {
        $restored->update(['is_declined' => true, 'revisor_id' => auth()->id()]);
        $restored->transaction()->create([
            'client_id' => $restored->client_id,
            'user_id' => auth()->id(),
            'club_id' => $restored->service->club_id,
            'amount' => $restored->restore_price,
            'description' => 'Отмена списания за восстановление услуги ' . $restored->service->name,
        ]);
        $restored->client->increment('balance', $restored->restore_price);
    }
}
