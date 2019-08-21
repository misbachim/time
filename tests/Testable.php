<?php

use App\Business\Model\Requester;

trait Testable
{
    public function getReqHeaders()
    {
        return [
            'tenantId' => 1000000000,
            'userId' => 1
        ];
    }

    public function getRequester()
    {
        $requester = new Requester;
        $requester->setTenantId(1000000000);
        $requester->setCompanyId(1900000000);
        $requester->setUserId(1);
        return $requester;
    }

    public function transform($arr)
    {
        $arrT = [];
        foreach ($arr as $field => $val) {
            $chunks = explode('_', $field);
            for ($i = 1; $i < count($chunks); $i++) {
                $chunks[$i] = ucfirst($chunks[$i]);
            }
            $arrT[implode($chunks)] = $val;
        }
        return $arrT;
    }

    public function exclude($arr, $keys)
    {
        foreach ($arr as &$item) {
            foreach ($keys as $key) {
                unset($item[$key]);
            }
        }
        return $arr;
    }

    public function include($arr, $maps)
    {
        foreach ($arr as &$item) {
            foreach ($maps as $key => $val) {
                $item[$key] = $val;
            }
        }

        return $arr;
    }
}
