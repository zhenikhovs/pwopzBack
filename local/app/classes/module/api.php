<?php

namespace Legacy\Api;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Module
{
    public static function GetCourseModulesInfo($courseID) {
        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Modules,
            'ACTIVE'=>'Y',
            'PROPERTY_COURSE'=>$courseID
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_UPPER_MODULE',
        ];

        $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, $arSelect);
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
                'upper_module' => $item['UPPER_MODULE'],
            ];
        }


        return $arResult;
    }

    public static function GetModule($arRequest) {
        $courseID = $arRequest['course_id'];
        $moduleID = $arRequest['module_id'];

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Modules,
            'ACTIVE'=>'Y',
            'ID'=>$moduleID
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_UPPER_MODULE',
            'PROPERTY_BLOCKS',
        ];

        $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, $arSelect);
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
                'blocks' => $item['BLOCKS'],
            ];
        }


        if(isset($arResult[0])) {
            return Helper::GetResponseApi(200, [
                'module_info' => $arResult[0],
                'modules' => self::GetCourseModulesInfo($courseID),
            ]);
        } else{
            return Helper::GetResponseApi(200, []);
        }
    }

    public static function GetReadModuleBefore($courseID, $moduleID) {
        global $USER;

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Result_modules,
            'ACTIVE'=>'Y',
            'USER'=>$USER->GetID(),
            'PROPERTY_COURSE' => $courseID,
            'PROPERTY_MODULE' => $moduleID,
        );

        $arSelect = [
            'ID'
        ];

        $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, false, $arSelect);
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

            $arResult[] = $item['ID'];
        }


        return count($arResult) > 0;
    }

    public static function AddReadModule($arRequest){
        global $USER;

        $courseID = $arRequest['course_id'];
        $moduleID = $arRequest['module_id'];


        $arLoadProperties = Array(
            'NAME' => 'Элемент',
            'IBLOCK_ID'=> \Legacy\Config::Result_modules,
            'ACTIVE'=>'Y',
            'PROPERTY_VALUES'=>[
                'MODULE'=>$moduleID,
                'COURSE'=>$courseID,
                'USER'=>$USER->GetID(),
            ]
        );

        if(!self::GetReadModuleBefore($courseID,$moduleID)){
            $el = new \CIBlockElement;
            if($res = $el->Add($arLoadProperties)){
                return Helper::GetResponseApi(200, []);
            } else{
                return Helper::GetResponseApi(404, [], 'Ошибка добавления:' . $res->LAST_ERROR);
            }
        }
        else {
            return Helper::GetResponseApi(200, []);
        }

    }

}