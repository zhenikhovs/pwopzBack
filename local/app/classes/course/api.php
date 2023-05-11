<?php

namespace Legacy\Api;
use Legacy\Api\User;
use Legacy\Api\Module;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Course
{
    public static function GetUserCourses() {
        global $USER;

        $arResultUserGroups = User::GetUserGroupsInfo();

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Courses,
                'ACTIVE'=>'Y',
                array(
                    "LOGIC" => "OR",
                    'PROPERTY_USER'=>$USER->GetID(),
                    'PROPERTY_USER_GROUP'=>$arResultUserGroups
                )
            );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_DESCRIPTION'
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
                'description' => $item['DESCRIPTION'],
            ];
        }


        return Helper::GetResponseApi(200, [
            'courses' => $arResult
        ]);
    }

    public static function GetCourse($arRequest) {
        $courseID = $arRequest['course_id'];

        return Helper::GetResponseApi(200, [
            'modules' => Module::GetCourseModulesInfo($courseID),
            'course_info' => self::GetCourseInfo($courseID)
        ]);
    }


    public static function GetCourseInfo($courseID) {
        global $USER;

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Courses,
            'ACTIVE'=>'Y',
            'ID'=>$courseID,
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_DESCRIPTION'
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
                'description' => $item['DESCRIPTION'],
            ];
        }

        if (isset($arResult[0])) {
            return $arResult[0];
        } else{
            return null;
        }
    }




}