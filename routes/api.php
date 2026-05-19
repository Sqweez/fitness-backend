<?php

use App\Http\Controllers\mobile\v1\AuthController;
use App\Http\Controllers\mobile\v1\ProfileController;
use App\Http\Controllers\mobile\v1\ClientRequestController;
use App\Models\Client;
use App\Models\Session;
use App\Http\Controllers\api\v1\{ClientBookmarkController,
    ClientController,
    ClubController,
    DashboardController,
    EconomyController,
    PenaltyController,
    ProductCategoryController,
    ProductController,
    RequestController,
    RestoredServiceController,
    RoleController,
    SaleController,
    ServiceController,
    SessionController,
    StatsController,
    UserController,
    UserReportController,
    WithDrawalController};
use Illuminate\Support\Facades\Route;

Route::get('photo-csv', function () {
    ini_set('memory_limit', '512M');
    $query =  Client::query()
        ->has('avatar')
        ->with('avatar')
        ->with('registrar:id,name')
        ->orderBy('id')
        ->withTrashed();
    $filename = "users.csv";
    $handle = fopen($filename, 'w+');
    $headers = ['client_id', 'photo', 'fio', 'phone'];
    fputcsv($handle, $headers, ';');
    $query
        ->cursor()
        ->each(function (Client $client) use (&$handle) {
            $array = [
                $client->id,
                $client->id . '.' . pathinfo($client->avatar->first()->getPath(), PATHINFO_EXTENSION),
                '',
                ''
            ];
            fputcsv($handle, $array, ";");
        });

    fclose($handle);

    return response()->download($filename)->deleteFileAfterSend(true);
});

Route::get('photo-download', function () {
    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', '1000000');
    $sourceFolder = base_path('storage/excel');
    $zipFileName = 'photo_' . time() . '.zip';
    $zipFilePath = public_path($zipFileName);
    $zip = new ZipArchive;

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        Client::query()
            ->has('avatar')
            ->with('avatar')
            ->withTrashed()
            ->orderBy('id')
            ->cursor()
            ->each(function ($client) use ($zip) {
                $avatar = $client->avatar->first();
                if ($avatar) {
                    $filePath = $avatar->getPath();
                    $newFileName = "{$client->id}." . pathinfo($filePath, PATHINFO_EXTENSION);
                    $zip->addFile($filePath, $newFileName);
                }
            });
        $zip->close();

        // Возвращаем архив как ответ на скачивание
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } else {
        return response()->json(['error' => 'Failed to create archive'], 500);
    }
});

Route::get('export', function () {
    $sourceFolder = base_path('storage/excel');
    // Создаем временное имя для архива
    $zipFileName = 'excel_' . time() . '.zip';
    $zipFilePath = public_path($zipFileName);

    // Создаем объект ZipArchive
    $zip = new ZipArchive;

    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        // Рекурсивно добавляем все файлы из папки в архив
        $files = File::allFiles($sourceFolder);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();

        // Возвращаем архив как ответ на скачивание
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    } else {
        return response()->json(['error' => 'Failed to create archive'], 500);
    }
});

Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api'
], function () {
    Route::apiResource('clubs', ClubController::class)->only(['index']);
    Route::apiResource('roles', RoleController::class)->only(['index']);
    Route::post('users/upload/{user}', [UserController::class, 'uploadPhoto']);
    Route::post('users/{user}/club', [UserController::class, 'chooseWorkingClub']);
    Route::get('users/{user}/reports', [UserReportController::class, 'index']);
    Route::post('users/{user}/restore', [UserController::class, 'restoreUser']);
    Route::apiResource('users', UserController::class);
    // Пополнение баланса
    Route::post('clients/{client}/top-up', [ClientController::class, 'topUpClientAccount']);
    // Переоформление карты
    Route::post('clients/{client}/pass', [ClientController::class, 'remakePass']);
    // История клиента
    Route::get('clients/{client}/history', [ClientController::class, 'getClientHistory']);
    Route::get('clients/search', [ClientController::class, 'search']);
    // История по выбранной/всем программам
    Route::get('clients/{client}/service/history', [ClientController::class, 'getServiceHistory']);
    Route::apiResource('clients', ClientController::class);
    // Активация купленной услуги
    Route::post('services/activate/{service}', [ServiceController::class, 'activateService']);
    // Редактирование купленной услуги
    Route::patch('services/purchased/{service}', [ServiceController::class, 'updatePurchaseService']);
    // Заморозка карты
    Route::post('services/stop/{service}', [ServiceController::class, 'stopPurchasedService']);
    Route::post('services/unstop/{service}', [ServiceController::class, 'unstopPurchasedService']);
    // Типы услуг
    Route::get('services/types', [ServiceController::class, 'getServiceTypes']);
    Route::apiResource('services', ServiceController::class);
    // Продажа услуги/бара
    Route::post('sale/service', [SaleController::class, 'createServiceSale']);
    Route::post('sale/product', [SaleController::class, 'createProductSale']);
    // Списание визита по услуге
    Route::get('session/finish/{client}', [SessionController::class, 'finish']);
    Route::post('session/attach/{client}', [SessionController::class, 'attach']);
    Route::post('session/solarium', [SessionController::class, 'writeOffSolarium']);
    Route::post('session/write-off', [SessionController::class, 'writeOffVisit']);
    // получение статистических данных
    Route::group(['prefix' => 'economy'], function () {
        Route::get('balance', [EconomyController::class, 'getClientsBalance']);
        Route::get('graphs', [EconomyController::class, 'getGraphReports']);
        Route::get('/', [EconomyController::class, 'index']);
        Route::group(['prefix' => 'my'], function () {
            Route::get('top-ups', [EconomyController::class, 'getMyTopUps']);
            Route::get('withdrawals', [EconomyController::class, 'getMyWithDrawals']);
            Route::get('bar', [EconomyController::class, 'getMyBar']);
            Route::get('sales', [EconomyController::class, 'getMySales']);
            Route::get('solarium', [EconomyController::class, 'getMySolarium']);
            Route::get('keys', [EconomyController::class, 'getKeys']);
        });
    });
    // Штрафное списание услуги
    Route::apiResource('penalty', PenaltyController::class);
    // Восстановление услуги
    Route::post('restored/{service}', [RestoredServiceController::class, 'store']);
    Route::patch('restored/{restored}', [RestoredServiceController::class, 'update']);
    // Получение списка запросов
    Route::group(['prefix' => 'requests'], function () {
        Route::get('/penalties', [RequestController::class, 'getPenaltiesRequests']);
        Route::get('/restored', [RequestController::class, 'getRestoredServiceRequests']);
    });
    // Получение данных для дэшборда
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('in-gym-clients', [DashboardController::class, 'getInGymClients']);
        Route::get('guests', [DashboardController::class, 'getGuestsClients']);
        Route::get('birthday', [DashboardController::class, 'getBirthdayClients']);
        Route::get('sleeping', [DashboardController::class, 'getSleepingClients']);
    });
    // Товары
    Route::post('products/{product}/batch', [ProductController::class, 'createProductBatch']);
    Route::get('products/{product}/batch', [ProductController::class, 'getProductBatchesInformation']);
    Route::apiResource('products/categories', ProductCategoryController::class);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::apiResource('products', ProductController::class);
    // Закладка клиентов
    Route::delete('bookmarks/{id}', [ClientBookmarkController::class, 'deleteBookmark']);
    Route::get('bookmarks', [ClientBookmarkController::class, 'index']);
    Route::post('bookmarks', [ClientBookmarkController::class, 'store']);

    Route::group(['prefix' => 'withdrawals'], function () {
        Route::post('/', [WithDrawalController::class, 'store']);
    });

    Route::group(['prefix' => 'stats'], function () {
        Route::get('/clients-by-club', [StatsController::class, 'getClientsByClub']);
        Route::get('/active-clients', [StatsController::class, 'getActiveClients']);
        Route::get('/price-history', [StatsController::class, 'getPriceSaleHistory']);
        Route::get('/wrong-birth', [StatsController::class, 'getWrongBirthDateClients']);
        Route::get('/unlimited-ending', [StatsController::class, 'getUnlimitedEndingClients']);
    });
});

Route::prefix('mobile')->middleware([])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('auth:api');
        });

        Route::get('profile', [ProfileController::class, 'getMyProfile']);
        Route::post('request', [ClientRequestController::class, 'store']);
        Route::get('club/stats', function () {
            return Session::query()
                ->whereNull('finished_at')
                ->today()
                ->get()
                ->groupBy('club_id')
                ->map(function ($values, $club_id) {
                    return [
                        'club_id' => $club_id,
                        'count' => is_countable($values) ? count($values) : 0,
                    ];
                })
                ->values();
        });

        Route::get('club/contacts', function () {
            return [
                [
                    'id' => 2,
                    'name' => 'Top Star Atrium',
                    'address' => 'Сатпаева 245/1, 3 этаж',
                    'instagram' => 'atriumtopstar',
                    'accentColor' => '#BBD620',
                    'instagramLink' => 'https://www.instagram.com/atriumtopstar',
                    'location' => null,
                    'phone' => '+7 707 747 90 04',
                    'lat' => '52.269389',
                    'lon' => '76.943300',
                    'map_url' => 'https://yandex.ru/map-widget/v1/?um=constructor%3A09fcfd09badf8c4f5c486d10120357c32800e71b2171eabf5f8e5d340c7cc40e&amp;source=constructor'
                ],
                [
                    'id' => 3,
                    'name' => 'Top Star Kids',
                    'address' => 'Сатпаева 245/1, 3 этаж',
                    'instagram' => 'topstar_kids',
                    'accentColor' => '#FFD600',
                    'instagramLink' => 'https://www.instagram.com/topstar_kids',
                    'location' => null,
                    'phone' => '+7 707 747 90 04',
                    'lat' => '52.269389',
                    'lon' => '76.943300',
                    'map_url' => 'https://yandex.ru/map-widget/v1/?um=constructor%3Aa7d289124f5f0bf5ad20be3b705f77795b2dc135fcae2d4aae1ba6d33d8b785e&amp;source=constructor'
                ],
                [
                    'id' => 1,
                    'name' => 'Top Star Женская Студия',
                    'address' => 'Машхура Жусупа 4/1',
                    'instagram' => 'topstar_women',
                    'accentColor' => '#FA00FF',
                    'instagramLink' => 'https://www.instagram.com/topstar_women',
                    'location' => null,
                    'phone' => '+7 707 747 90 04',
                    'lat' => '52.295652',
                    'lon' => '76.950774',
                    'map_url' => 'https://yandex.ru/map-widget/v1/?um=constructor%3A4185ae09a8a37724d4a95b93ee397f96c86e735ea7deb5bbe3401ba43622f6d9&amp;source=constructor'
                ],
            ];
        });

        Route::get('club/team', function (\Illuminate\Http\Request $request) {
            $users = \App\Models\User::query()
                ->where('is_active', true)
                ->when($request->filled('club_id'), function ($query) use ($request) {
                    return $query->whereHas('club', function ($q) use ($request) {
                        return $q->where('id', $request->get('club_id'));
                    });
                })
                ->with(['media'])
                ->get();

            return \App\Http\Resources\User\SingleUserResource::collection($users);
        });
    });
});

require __DIR__ . '/auth.php';

