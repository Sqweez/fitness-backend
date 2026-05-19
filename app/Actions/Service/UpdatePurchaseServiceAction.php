<?php

namespace App\Actions\Service;

use App\Models\ServiceSale;
use Illuminate\Http\Request;

class UpdatePurchaseServiceAction {

    public function handle(Request $request, ServiceSale $service): \App\Models\Sale {
        $service->update($request->all());
        return $service->sale;
    }

}
