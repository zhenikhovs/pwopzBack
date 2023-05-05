<?php

namespace Legacy;

use CPHPCache;
use http\Message;
use Legacy\Content as ContentGeneral;
use Bitrix\Main\Loader;

Loader::includeModule("highloadblock");

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class Helper
{
//     формирование ответа для api
    public static function GetResponseApi(int $iCode, $arResult = [], $errors = ''): array
    {
        if ($iCode == 200) {
            return [
                'status' => 'ok',
                'error' => 0,
                'error_message' => '',
                'result' => $arResult
            ];
        } else {
            return [
                'status' => 'error',
                'error' => 1,
                'error_message' => $errors,
                'result' => ['message' => $errors, 'res' => $arResult]
            ];
        }
    }


    public static function GetResponseAjax($code, $data)
    {
        if ($code) {
            $code = 200;
        } else {
            $code = 422;
        }

        $arData = $data;
        $arData["code"] = $code;
        $arData["status"] = $data['message'];

        return $arData;
    }
}