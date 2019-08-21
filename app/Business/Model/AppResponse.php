<?php

namespace App\Business\Model;

use Illuminate\Http\Response;

/**
 * JSON Response format
 *
 * @package App\Common
 */
class AppResponse
{
    var $status; //status of response. Usually same with http status code. If 200, no need to specify explicitly
    var $message; //message to client
    var $data; //data that related to business logic

    function __construct($data, $message, $status = Response::HTTP_OK)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = is_null($data) ? array() : $data;;
    }
}
