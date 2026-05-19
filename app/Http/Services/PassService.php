<?php

namespace App\Http\Services;

use App\Models\Pass;
use App\Models\User;

class PassService {

    public static function createPass($passCode): Pass {
        return Pass::updateOrCreate([
            'code' => $passCode
        ]);
    }

    public static function detachPass($passCode) {
        $pass = Pass::whereCode($passCode)->first();
        if ($pass) {
            $pass->delete();
        }
    }
}
