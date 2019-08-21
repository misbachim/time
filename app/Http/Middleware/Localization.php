<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Middleware for handling i18n in REST architecture
 */
class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // read the language from the request header
        $locale = $request->header('Accept-Language');

        // if the header is missed
        if (!$locale) {
            // take the default locale language
            $locale = config('app.locale');
        }

        //set locale language
        config(['app.locale' => $locale]);

        // get the response after the request is done
        $response = $next($request);

        // set Content Languages header in the response
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
