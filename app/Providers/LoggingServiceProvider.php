<?php

namespace App\Providers;

use App\Business\Model\Requester;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * Service provider for configuring logging mechanism
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    public function boot(){
        $maxFiles = env('LOG_MAX_FILE_KEEP', 30);
        $logPathFile = env('LOG_FILE', storage_path('logs/hr-api.log'));
        $logLevel = env('LOG_LEVEL', Logger::ERROR);

        $handlers[] = (new RotatingFileHandler($logPathFile, $maxFiles,
            $logLevel))->setFormatter(new LineFormatter(null, null, true, true));

        $this->app['log']->setHandlers($handlers);
    }
}
