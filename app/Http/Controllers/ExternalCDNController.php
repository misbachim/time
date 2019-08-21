<?php

namespace App\Http\Controllers;

use App\Business\Helper\HttpClient;
use App\Business\Model\Requester;

/**
 * Controller for communication with cdn microservice
 * @package App\Http\Controllers
 */
class ExternalCDNController extends Controller
{
    public static $DOC_URI = '/doc/';

    private $cdnServiceUrl;
    private $requester;

    public function __construct(Requester $requester)
    {
        $this->cdnServiceUrl = env('CDN_SERVICE_API');
        $this->requester = $requester;
    }

    public function doc($ref, $fileId)
    {
        $url = $this->cdnServiceUrl . ExternalCDNController::$DOC_URI.$this->requester->getCompanyId().'/'.$ref .'/'.$fileId;
        info('url',[$url]);
        return HttpClient::get($url, $this->requester);
    }
}
