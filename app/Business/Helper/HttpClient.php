<?php

namespace App\Business\Helper;

use App\Business\Model\Requester;
use App\Exceptions\AppException;
use GuzzleHttp\Client;
use Illuminate\Http\Response;

/**
 * Helper class to call external resource.
 * @package App\Business\Helper
 */
class HttpClient
{
    /**
     * Call external resource using POST method.
     *
     * @param string $url
     * @param $body
     * @param Requester $requester
     * @return mixed
     */
    public static function post(string $url, $body, Requester $requester)
    {
        $client = new Client([
            'base_uri' => $url,
            'body' => json_encode($body),
            'headers' =>
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $requester->getToken(),
                    'Origin' => 'httpclient'
                ]
        ]);

        $response = $client->request('POST')->getBody()->getContents();
        $decoded = json_decode($response, true);
        if (is_array($decoded) && $decoded['status'] != Response::HTTP_OK) {
            throw new AppException($decoded['message']);
        }
        return is_array($decoded) ? (object) $decoded : $response;
    }

    /**
     * Call external resource using GET method.
     *
     * @param string $url
     * @param Requester $requester
     * @return mixed
     */
    public static function get(string $url, Requester $requester = null)
    {
        if ($requester) {
            $client = new Client([
                'base_uri' => $url,
                'headers' => [
                    'Authorization' => 'Bearer ' . $requester->getToken(),
                    'Origin' => 'httpclient'
                ]
            ]);
        } else {
            $client = new Client(['base_uri' => $url]);
        }

        $response = $client->request('GET')->getBody()->getContents();
        $decoded = json_decode($response, true);
        if (is_array($decoded) && $decoded['status'] != Response::HTTP_OK) {
            throw new AppException($decoded['message']);
        }
        return is_array($decoded) ? (object) $decoded : $response;
    }
}
