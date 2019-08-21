<?php

namespace App\Http\Controllers;

use App\Business\Model\AppResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * Base Class of every controller
 */
class Controller extends BaseController
{
    public function __construct()
    {
        //
    }

    /**
     * Render response JSON format to client
     *
     * @param AppResponse $appResponse
     * @param integer $status status of response
     * @return AppResponse / JSON
     * @internal param any $data data of response
     * @internal param string $message message of response
     */
    protected function renderResponse(AppResponse $appResponse, $status = Response::HTTP_OK)
    {
        return response()->json($appResponse, $status);
    }

    /**
     * Format for ValidationException message
     *
     * @param Validator $validator
     * @return void custom message
     */
    public function formatValidationErrors(Validator $validator)
    {
        $errorMessage = parent::formatValidationErrors($validator);
        $customMessage = array();

        foreach ($errorMessage as $key => $message) {
            $customMessage[] = array('key' => $key, 'message' => $message);
        }

        return $customMessage;
    }
}
