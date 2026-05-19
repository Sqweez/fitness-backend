<?php

namespace App\Console\Commands\Imports;

use App\Http\Services\PassService;
use App\Models\Client;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;

class ImportClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортируем клиентов из старой базы';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //\DB::table('clients')->truncate();
        $clients = $this->getClients();
        $hasClients = count($clients) > 0;
        collect($clients)->each(function ($client) {
            $this->line('Клиент: ' . $client->name);

            try {
                $birth_date = $client->datarozhd !== '0000-00-00' ? Carbon::parse($client->datarozhd): now();
            } catch (InvalidFormatException $exception) {
                \Log::error($exception->getMessage());
                $birth_date = now();
            }

            try {
                $created_at = Carbon::parse($client->datarega);
            } catch (InvalidFormatException $exception) {
                \Log::error($exception->getMessage());
                $created_at = now();
            }

            $_client = Client::updateOrCreate([
                'id' => $client->id,
            ],[
                'name' => $client->name,
                'phone' => '+7' . unmask_phone($client->phone),
                'birth_date' => $birth_date,
                'description' => $client->comment,
                'balance' => $client->balans,
                'club_id' => str_replace('club', '', $client->club),
                'user_id' => $client->who == 0 ? null : $client->who,
                'created_at' => $created_at,
                'gender' => intval($client->pol) === 1 ? 'M' : 'F',
                'cached_pass' => $client->cardid,
                'cached_solarium_total' => $client->solar
            ]);

            if ($client->cardid) {
                $pass = PassService::createPass($client->cardid);
                $_client->pass()->save($pass);
            }

            /*if ($client->photo && !\Str::contains($client->photo, 'nophoto')) {
                $photoExtension = explode('.', $client->photo)[1];
                if (in_array($photoExtension, ['jpeg', 'jpg', 'png'])) {
                    $this->line($client->photo);
                    try {
                        $_client
                            ->addMediaFromUrl(sprintf("http://top-star.kz/photos/%s", $client->photo))
                            ->toMediaCollection(Client::MEDIA_AVATAR);;
                    } catch (\Exception $exception) {
                        \Log::error($exception->getMessage());
                    }
                }
            }*/
        });
    }

    private function getClients() {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_clients.php');
        return json_decode($response->getBody());
    }
}
