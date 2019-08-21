<?php

namespace App\Exceptions;

use App\Business\Model\AppResponse;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof HttpException || $e instanceof AppException) {
            return response()->json(new AppResponse(null, $e->getMessage(), $e->getStatusCode()), $e->getStatusCode());
        }

        if ($e instanceof ValidationException) {
            //status code change to 444. Means error caused by field validation violation.
            //Client must be aware of this problem!
            return response()->json(
                new AppResponse($e->getResponse()->original, $e->getMessage(), 444),
                Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json(new AppResponse(null, 'Unauthorized.', Response::HTTP_UNAUTHORIZED), Response::HTTP_UNAUTHORIZED);
        }

        //Guzzle Request Exception goes here
        if ($e instanceof RequestException) {
            return response()->json(json_decode($e->getResponse()->getBody()->getContents()), $e->getResponse()->getStatusCode());
        }

        //default message for undefined exception, must return a JSON message. For tracing, please refers to log file.
        return response()->json(
            new AppResponse(null, "Oops... something happen. Please try again later.", Response::HTTP_INTERNAL_SERVER_ERROR),
            Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
