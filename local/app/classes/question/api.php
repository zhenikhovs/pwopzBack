<?php

namespace Legacy\Api;
use Legacy\Api\Module;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Question
{
    public static function GetQuestionsInfo($testID) {

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Questions,
            'ACTIVE'=>'Y',
            'PROPERTY_TEST'=>$testID,
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_ANSWERS',
            'PROPERTY_ANSWER_TYPE'
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);
        $arResult = [];

        while($item = $res->Fetch()){
            foreach($item as $key => $value){
                if(strripos($key,'VALUE_ID')){
                    unset($item[$key]);
                    continue;
                }
                if(strripos($key,'PROPERTY') !== false){
                    $old_key = $key;
                    $key = str_replace(['PROPERTY_','_VALUE','~'], '', $key);
                    $item[$key] = $value;
                    unset($item[$old_key]);
                }
            }

            $arResult[] = [
                'ID' => $item['ID'],
                'name' => $item['NAME'],
                'answers' => $item['ANSWERS'],
                'answer_type' => $item['ANSWER_TYPE'],
            ];
        }

        return $arResult;
    }

    //перенести в резалт
    public static function GetQuestionsAnswersInfo($testID) {

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Questions,
            'ACTIVE'=>'Y',
            'PROPERTY_TEST'=>$testID,
        );

        $arSelect = [
            'ID',
            'PROPERTY_CORRECT_ANSWERS'
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);
        $arResult = [];

        while($item = $res->Fetch()){
            foreach($item as $key => $value){
                if(strripos($key,'VALUE_ID')){
                    unset($item[$key]);
                    continue;
                }
                if(strripos($key,'PROPERTY') !== false){
                    $old_key = $key;
                    $key = str_replace(['PROPERTY_','_VALUE','~'], '', $key);
                    $item[$key] = $value;
                    unset($item[$old_key]);
                }
            }

            $arResult[$item['ID']] = $item['CORRECT_ANSWERS'];
        }

        return $arResult;
    }




}