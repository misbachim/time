<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log, DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    { }

    public function boot(Request $request)
    {
        // DB::listen(function ($query) {
        //     Log::critical(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });

        // Log database query
        Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
            Log::debug("SQL : " . $query->sql .
                ' (' . json_encode($query->bindings) . ') in (' . $query->time . ' ms)');
        });

        AppServiceProvider::createValidators();
    }

    public function createValidators()
    {
        // min_field custom validator
        Validator::extend('min_field', function ($attribute, $value, $parameters, $validator) {
            $nestedFields = explode('.', $parameters[0]);
            $data = $validator->getData();
            foreach ($nestedFields as $field) {
                if (!array_key_exists($field, $data)) {
                    return false;
                }
                $data = $data[$field];
            }
            return $value >= $data;
        });

        Validator::replacer('min_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });

        // max_field custom validator
        Validator::extend('max_field', function ($attribute, $value, $parameters, $validator) {
            $nestedFields = explode('.', $parameters[0]);
            $data = $validator->getData();
            foreach ($nestedFields as $field) {
                if (!array_key_exists($field, $data)) {
                    return false;
                }
                $data = $data[$field];
            }
            return $value <= $data;
        });

        Validator::replacer('max_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', $parameters[0], $message);
        });
    }
}
