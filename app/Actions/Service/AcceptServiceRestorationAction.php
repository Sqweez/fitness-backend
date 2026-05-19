<?php

namespace App\Actions\Service;

use App\Models\RestoredService;

class AcceptServiceRestorationAction {

    public function handle(RestoredService $restored): RestoredService {
        $restored->update(['is_accepted' => true, 'revisor_id' => auth()->id()]);
        return $restored;
    }

}
