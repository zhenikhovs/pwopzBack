<?php

namespace Legacy\Api;
use Legacy\Api\Module;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Api\Group;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Result
{

    public static function GetUserCoursesProgress(){
        $userCourses = Course::GetUserCoursesInfo();

        $userCoursesInfo = [];
        foreach ($userCourses as $course){
            $courseModules = Module::GetCourseModulesInfo($course['id']);
            $readCourseModules = self::GetReadCourseModulesInfo($course['id']);

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

    public static function GetReadCourseModulesInfo($courseID, $userID = null){
        global $USER;
        if (!$userID) {
            $userID = $USER->GetID();
        }

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Result_modules,
            'ACTIVE'=>'Y',
            'PROPERTY_USER'=>$userID,
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


    public static function GetStatisticsCourses(){
        $courses = Course::GetCoursesInfo();
        $groups = Group::GetGroupsInfo();

        $arResult = [];

        //проходимся по курсам
        foreach ($courses as $course){

            $courseModules = Module::GetCourseModulesInfo($course['id']);

            $userFromGroups = [];
            //проходимся по группам которым назначен курс
            foreach ($course['user_group'] as $group){

                //находим эту группы в общем списке групп
                $necessaryGroup = array_filter($groups, function($g) use ($group) {
                    return $g['id'] === $group;
                });
                //достаем пользователей этой группы
                $userFromGroups = array_values(array_unique(array_merge($userFromGroups, array_values($necessaryGroup)[0]['users'])));
            }

            //все пользователи кому назначен курс
            $allUsers = array_values(array_unique(array_merge($userFromGroups,$course['user'])));

            $userProgressInfo = [];
            $usersInfo = [];
            foreach ($allUsers as $user){
                $usersInfo[$user] = User::GetUserInfo($user);
                $userProgressInfo[$user] = [
                    'read_modules' => self::GetReadCourseModulesInfo($course['id'], $user),
                    'test_results' => Test::GetDoneTestBefore($course['id'],$user),
                ];
            }

            $arResult[] = [
                "id"=> $course['id'],
                "course_name"=> $course['name'],
                "course_description"=> $course['description'],
                "test_id"=> $course['test_id'],
                "individual_users"=> $course['user'],
                "groups"=> $course['user_group'],
                'user_from_groups' => $userFromGroups,
                'all_users' => $allUsers,
                'users_info' => $usersInfo,
                'modules' => $courseModules,
                'users_progress' =>$userProgressInfo
            ];
        }



        return Helper::GetResponseApi(200, [
            'courses_progress' => $arResult
        ]);
    }




















    public static function GetStatisticsUsers(){

        return Helper::GetResponseApi(200, [
            'user_courses_progress' => []
        ]);
    }

    public static function GetStatisticsGroups(){

        return Helper::GetResponseApi(200, [
            'user_courses_progress' => []
        ]);
    }





}