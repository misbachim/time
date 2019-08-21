<?php

namespace App\Business\Model;

use Illuminate\Http\Response;

/**
 * JSON Response format
 *
 * @package App\Common
 */
class PagingAppResponse extends AppResponse
{
    var $pageInfo;

    function __construct($data, $message, int $pageLimit, int $totalRows, int $pageNo, $status = Response::HTTP_OK)
    {
        parent::__construct($data, $message, $status);
        $totalPages = ceil($totalRows / $pageLimit);
        $this->pageInfo = [
            'pageLimit' => $pageLimit,
            'totalRows' => $totalRows,
            'pageNo' => $pageNo,
            'totalPages' => $totalPages
        ];
    }

    static function getOffset($paging)
    {
        return ($paging['pageNo'] - 1) * $paging['pageLimit'];
    }

    static function getPageNo($paging)
    {
        return $paging['pageNo'];
    }

    static function getPageLimit($paging)
    {
        return $paging['pageLimit'];
    }

    static function getKeyword($keyword)
    {
        return $keyword['keyword'];
    }

    static function getOrder($order)
    {
        return $order['order'];
    }

    static function getOrderDirection($order)
    {
        return $order['orderDirection'];
    }
}
