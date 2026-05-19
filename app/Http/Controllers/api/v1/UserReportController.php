<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\UserReports\RetrieveUserReportsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserReportController extends ApiController
{
    public function index(User $user, Request $request, RetrieveUserReportsAction $action): array {
        return $action
            ->handle(
                $user,
                $request->get('start'),
                $request->get('finish')
            );
    }
}
