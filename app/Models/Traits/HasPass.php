<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasPass {

    public function scopePass(Builder $query, $pass): Builder {
        return $query->whereHas('pass', function (Builder $builder) use ($pass) {
            return $builder->whereCode($pass);
        });
    }
}
