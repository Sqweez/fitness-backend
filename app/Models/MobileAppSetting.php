<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileAppSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'contacts' => 'array',
        'app' => 'array',
        'maintenance' => 'array',
        'links' => 'array',
    ];
}
