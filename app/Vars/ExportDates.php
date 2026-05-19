<?php

namespace App\Vars;

use Carbon\Carbon;

class ExportDates
{

    public $start;
    public $finish;
    public function __construct()
    {
        $this->start = Carbon::create(2018, 1, 1)->startOfDay();
        $this->finish = Carbon::create(2024, 2, 2)->endOfDay();
    }
}
