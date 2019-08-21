<?php

namespace App\Exceptions;

use Illuminate\Http\Response;
use \Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Application Exception
 *
 * Use this exception if found invalid business logic and framework will
 * return json response. Default status code is 422 (Unprocessable Entity).
 */
class AppException extends HttpException
{
    private $statusCode;
    protected $message;

    public function __construct(string $message, int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        $this->statusCode = $statusCode;
        $this->message = $message;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
