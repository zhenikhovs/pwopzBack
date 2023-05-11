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

}