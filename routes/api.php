<?php

use App\Http\Controllers\Api\V2\EconomyReportController;
use App\Http\Controllers\Api\V1\{ClientBookmarkController,
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
    TrainerController,
    UserController,
    UserReportController,
    WithDrawalController};
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api'
], function () {
    Route::group(['middleware' => 'can:manage-trainers'], function () {
        Route::apiResource('trainers', TrainerController::class);
    });

    Route::apiResource('clubs', ClubController::class)->only(['index']);
    Route::group(['middleware' => 'can:manage-club,club'], function () {
        Route::apiResource('clubs', ClubController::class)->only(['show', 'update']);
    });
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

Route::group([
    'prefix' => 'v2',
    'middleware' => 'auth:api'
], function () {
    Route::group(['prefix' => 'economy'], function () {
        Route::get('top-ups', [EconomyReportController::class, 'topUps']);
        Route::get('programs', [EconomyReportController::class, 'programs']);
        Route::get('guests', [EconomyReportController::class, 'guests']);
        Route::get('new-clients', [EconomyReportController::class, 'newClients']);
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/mobile.php';

