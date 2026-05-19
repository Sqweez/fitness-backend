<?php

namespace App\Repositories\Client;

use App\Models\Client;

class RetrieveSingleClient {

    public static function retrieve(Client $client): Client {
        $client->load('pass');
        $client->load('registrar');
        $client->load('club');
        $client->load('lastPrograms');
      //  $client->load('activeSolariumMinutes.salable.visits:id,service_sale_id,minutes');
        $client->load('active_session');
        return $client;
    }
}
