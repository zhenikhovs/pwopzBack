<?php

namespace Legacy\Api;
use Legacy\Api\Module;
use Legacy\Api\User;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Course
{

    public static function GetUserCourses() {
        return Helper::GetResponseApi(200, [
            'courses' => self::GetUserCoursesInfo()
        ]);
    }

    public static function GetUserCoursesInfo() {
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
            'PROPERTY_DESCRIPTION',
            'PROPERTY_TEST',
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
                'id' => $item['ID'],
                'name' => $item['NAME'],
                'description' => $item['DESCRIPTION'],
                'test_id' => $item['TEST'],
            ];
        }
        return $arResult;
    }

    public static function GetGroupCoursesInfo($group) {
        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Courses,
            'ACTIVE'=>'Y',
            'PROPERTY_USER_GROUP' => $group
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_DESCRIPTION',
            'PROPERTY_TEST',
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
                'id' => $item['ID'],
                'name' => $item['NAME'],
                'description' => $item['DESCRIPTION'],
                'test_id' => $item['TEST'],
            ];
        }
        return $arResult;
    }

    public static function GetCourses() {
        return Helper::GetResponseApi(200, [
            'courses' => self::GetCoursesInfo()
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
            'PROPERTY_DESCRIPTION',
            'PROPERTY_USER',
            'PROPERTY_USER_GROUP',
            'PROPERTY_TEST',
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
                'users' => $item['USER'],
                'groups' => $item['USER_GROUP'],
                'test' => $item['TEST'],
            ];
        }

        if (isset($arResult[0])) {
            return $arResult[0];
        } else{
            return null;
        }
    }

    public static function GetCoursesTestsInfo() {
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
            'PROPERTY_TEST'
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);
        $arCoursesTests = [];
        $arTestsIDs = [];


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

            if ($item['TEST']){
                $arTestsIDs[] = $item['TEST'];
                $arCoursesTests[] = [
                    'test_id' => $item['TEST'],
                    'name'=> $item['NAME'],
                    'course_id' =>$item['ID']
                ];
            }
        }

       return [
           'tests_ids' => $arTestsIDs,
           'courses_tests_info' => $arCoursesTests,
       ];
    }

    public static function GetCoursesInfo($IDs = []) {

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Courses,
            'ACTIVE'=>'Y',
            'ID' => $IDs
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_DESCRIPTION',
            'PROPERTY_TEST',
            'PROPERTY_USER',
            'PROPERTY_USER_GROUP',
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
                'id' => $item['ID'],
                'name' => $item['NAME'],
                'description' => $item['DESCRIPTION'],
                'test_id' => $item['TEST'],
                'user_group' => $item['USER_GROUP'],
                'user' => $item['USER'],
            ];
        }
        return $arResult;
    }


}