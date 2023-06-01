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


     public static function CurlBitrix24($method, $arData=array()){
        $queryUrl = "https://legacy.bitrix24.ru/rest/16/4dnhn1xtr5kp6hup/".$method;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $queryUrl,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ));
        if(!empty($arData)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($arData));
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result,true);
    }
}