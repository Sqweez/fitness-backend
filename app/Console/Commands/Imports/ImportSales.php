<?php

namespace App\Console\Commands\Imports;

use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;

class ImportSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортирует продажи услуг';

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
     * @return int
     */

/*    public function handle() {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('sales')->truncate();
        \DB::table('service_sale')->truncate();
        \DB::table('transactions')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $sales = \Storage::disk('public')->get('historyprodazh_prog.json');
        $sales = json_decode($sales, true);
        $this->line(count($sales));
        $services = Service::all();
        collect($sales)->each(function ($sale, $key) use ($services) {
            $this->line($key . $sale['nazvpr']);
            $service = $services->where('id', $sale['uslugi'])->first();
            $entriesCount = $service->service_type_id === Service::TYPE_PROGRAM ? $service->entries_count : null;
            try {
                $activeUntil = $sale->dataend !== '0000-00-00' ? Carbon::parse($sale->dataend): now()->addYear();
            } catch (InvalidFormatException $exception) {
                \Log::error($exception->getMessage());
                $activeUntil = now()->addYear();
            }
            $serviceSale = ServiceSale::query()
                ->create([
                    'service_id' => $sale['uslugi'],
                    'entries_count' => $entriesCount,
                    'minutes_remaining' => $services->service_type_id === Service::TYPE_SOLARIUM ? $sale['validity_minutes'] : null,
                    'active_until' => $activeUntil,
                    'is_prolongation' => false,
                    'activated_at' => $sale->activation == 0 ? null : now(),
                    'self_name' => $sale->nazvpr,
                    'created_at' => $sale->data,
                    'updated_at' => $sale->data,
                    'id' => $sale->id
                ]);
        });
    }*/

    public function handle(): int {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        /*\DB::table('sales')->truncate();
        \DB::table('service_sale')->truncate();
        \DB::table('transactions')->truncate();*/
        $hasData = true;
        $page = 1;
        while ($hasData) {
            $sales = $this->getSales($page);
            $hasData = count($sales) > 0;
            if (!$hasData) {
                $this->line('Экспорт завершен!');
            }
            collect($sales)->each(function ($sale) {
                #$this->line($sale->nazvpr);
                $serviceSale = $this->createServiceSale($sale);
                $_sale = $serviceSale->sale()->create($this->getSaleObject($sale));
                $_sale->transaction()->create($this->getTransactionObject($sale));
            });
            $this->line('Импортировано ' . $page * 50000);
            $page++;
        }
        ServiceSale::whereServiceId(1174)->update(['entries_count' => 1]);
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return 0;
    }

    private function getSales($page) {
        $this->line('Начато получение');
        $client = new \GuzzleHttp\Client();
        $response = $client->get('http://top-star.kz/export/export_sales.php?page=' . $page);
        $this->line('Закончено получение');
        return json_decode($response->getBody());
    }

    private function createServiceSale($sale): ServiceSale {
        $oldServiceTypeId = intval($sale->class);
        $serviceTypeId = 1;
        if ($oldServiceTypeId === 0) {
            $serviceTypeId = Service::TYPE_UNLIMITED;
        }
        if ($oldServiceTypeId === 1) {
            $serviceTypeId = Service::TYPE_PROGRAM;
        }
        if ($oldServiceTypeId === 2) {
            $serviceTypeId = Service::TYPE_SOLARIUM;
        }
        $entriesCount = $serviceTypeId === Service::TYPE_PROGRAM ? $sale->kolvo : null;

        if ($sale->uslugi == 1174) {
            $entriesCount = 1;
        }

        try {
            $activeUntil = $sale->dataend !== '0000-00-00' ? Carbon::parse($sale->dataend): null;
        } catch (InvalidFormatException $exception) {
            \Log::error($exception->getMessage());
            $activeUntil = null;
        }

        $serviceSaleObject = [
            'service_id' => $sale->uslugi,
            'entries_count' => $entriesCount,
            'minutes_remaining' => $serviceTypeId === Service::TYPE_SOLARIUM ? $sale->kolvo : null,
            'active_until' => $activeUntil,
            'is_prolongation' => false,
            'activated_at' => $sale->activation == 0 ? null : now(),
            'self_name' => $sale->nazvpr,
            'created_at' => $sale->data,
            'updated_at' => $sale->data,
            'id' => $sale->id
        ];

        return ServiceSale::create($serviceSaleObject);
    }

    private function getSaleObject($sale): array {
        $clubId = str_replace('club', '', $sale->clubid);
        return [
            //'id' => $sale->id,
            'client_id' => $sale->clientid,
            'club_id' => $clubId ?: 2,
            'user_id' => $sale->kto,
            'salable_type' => 'App\\Models\\ServiceSale',
            'created_at' => $sale->data,
            'updated_at' => $sale->data,
        ];
    }

    private function getTransactionObject($sale): array {
        $description = sprintf("Списание средств за %s", $sale->nazvpr);
        $clubId = str_replace('club', '', $sale->clubid);
        return [
            'user_id' => $sale->kto,
            'client_id' => $sale->clientid,
            'amount' => intval($sale->summa) * -1,
            'club_id' => $clubId ?: 2,
            'description' => $description,
            'updated_at' => $sale->data,
            'created_at' => $sale->data,
        ];
    }
}
