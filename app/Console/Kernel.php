<?php

namespace App\Console;

use App\Console\Commands\DestroyUserClubs;
use App\Console\Commands\Export\ExportCommand;
use App\Console\Commands\FinishClientSessions;
use App\Console\Commands\Utils\CollectHasActiveProgramsClients;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command(FinishClientSessions::class)->dailyAt('00:00');
        $schedule->command(DestroyUserClubs::class)->dailyAt('00:00');
        $schedule->command(CollectHasActiveProgramsClients::class)->dailyAt('00:05');
        $schedule->command(ExportCommand::class)->dailyAt('00:10');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
