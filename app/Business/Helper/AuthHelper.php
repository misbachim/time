<?php

use App\Business\Dao\SessionDao;
use Carbon\Carbon;

function isTokenBlocked($token)
{
    $isBlocked = false;
    $authService = 'um';

    if (env('SERVICE_NAME') !== $authService) {
        exchangeOnce(
            env('SERVICE_NAME'),
            'tokenstatus',
            $authService,
            null,
            [
                'token' => $token
            ],
            function ($msg) use (&$isBlocked) {
                $isBlocked = $msg->data->isBlocked;
            }
        );
    } else {
        $sessionDao = new SessionDao;
        $session = $sessionDao->getOneByToken($token);
        if ($session !== null) {
            return $session->isBlocked;
        }
    }

    return $isBlocked;
}

function expireToken($token)
{
    $authService = 'um';

    if (env('SERVICE_NAME') !== $authService) {
        send(env('SERVICE_NAME'), 'expiretoken', $authService, null, ['token' => $token]);
    } else {
        $sessionDao = new SessionDao;
        $sessionDao->updateByToken($token, [
            'logout_time' => Carbon::now()->timestamp
        ]);
    }
}

function renewToken($oldToken, $newToken)
{
    $authService = 'um';

    if (env('SERVICE_NAME') !== $authService) {
        send(env('SERVICE_NAME'), 'renewtoken', $authService, null, [
            'oldToken' => $oldToken,
            'newToken' => $newToken
        ]);
    } else {
        $sessionDao = new SessionDao;
        $sessionDao->updateByToken($oldToken, [
            'last_used_token' => $newToken
        ]);
    }
}
