<?php

namespace Legacy\Api;
use Legacy\Api\Module;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Result
{

    public static function GetCoursesProgress(){
        $userCourses = Course::GetUserCoursesInfo();

        $userCoursesInfo = [];
        foreach ($userCourses as $course){
            $courseModules = Module::GetCourseModulesInfo($course['id']);
            $readCourseModules = self::GetReadCourseModules($course['id']);

            $testInfo = null;

            if($course['test_id']){
                $testInfo = [
                    'name'=>Test::GetTestInfo($course['test_id'])['name']
                ];

                $testDoneBefore = Test::GetDoneTestBefore($course['id']);
                if($testDoneBefore){
                    $testInfo['questions_count'] = $testDoneBefore['questions_count'];
                    $testInfo['correct_answers'] = $testDoneBefore['correct_answers'];
                }

            }

            $modulesDetail = [];

            foreach ($courseModules as $courseModule){
                $modulesDetail[$courseModule['name']] = in_array($courseModule['id'], $readCourseModules);
            }

            $userCoursesInfo[] = [
                'modules_info' =>[
                    'modules_count' => count($courseModules),
                    'modules_read' => count($readCourseModules),
                    'modules_detail' => $modulesDetail,
                ],
                'course_name' => $course['name'],
                'id' => $course['id'],
                'test_info' =>$testInfo
            ];
        }

        return Helper::GetResponseApi(200, [
            'user_courses_progress' => $userCoursesInfo
        ]);
    }

    public static function GetReadCourseModules($courseID){
        global $USER;

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Result_modules,
            'ACTIVE'=>'Y',
            'USER'=>$USER->GetID(),
            'PROPERTY_COURSE' => $courseID,
        );

        $arSelect = [
            'PROPERTY_MODULE'
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

            $arResult[] = $item['MODULE'];
        }


        return $arResult;
    }


}