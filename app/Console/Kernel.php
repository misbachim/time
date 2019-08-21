<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Events\IncomingMessageEvent;
use App\Jobs\ResetLeaveQuotaJob;
use App\Business\Model\Requester;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    private $connectionUm = 'pgsql_um';
    private $connectionCore = 'pgsql_core';

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            receive('core', '*', '*', function ($msg) {
                event(new IncomingMessageEvent($msg));
            });
        })->name('listening')->daily();
        $schedule->call(function ()  use ($schedule) {
            $schedule->job(dispatch(new ResetLeaveQuotaJob()), 'reset-leave-quota');
//        })->everyFiveMinutes();
        })->dailyAt('01:00')->timezone('Asia/Bangkok');
    }

    private function getAllActiveTenantId()
    {
        return
            DB::connection($this->connectionUm)
            ->table('tenants')
            ->where([
                ['is_deleted', false]
            ])
            ->whereRaw('? between eff_begin and eff_end', [Carbon::today()])
            ->select('id')
            ->get();
    }

    public function getAllCompanyId($activeTenant)
    {
        return DB::connection($this->connectionCore)
            ->table('companies')
            ->select(
                'id'
            )
            ->whereIn('tenant_id', $activeTenant)
            ->get();
    }
}
