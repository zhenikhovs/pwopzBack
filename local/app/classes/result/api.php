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

            $averageCourseProgress = 0;
            $averageTestProgress = 0;
            $testsCount = 0;

            $userProgressInfo = [];
            $usersInfo = [];
            foreach ($allUsers as $user){
                $usersInfo[$user] = User::GetUserInfo($user);
                //прочитанные модули пользователя
                $readModules = self::GetReadCourseModulesInfo($course['id'], $user);
                //проверка на наличие модулей в курсе
                if(count($courseModules) !==0 ){
                    $averageCourseProgress += count($readModules)/count($courseModules);
                }

                //результаты тестирования пользователя (если нет false)
                $testResults = Test::GetDoneTestBefore($course['id'],$user);
                if($testResults){
                    //увеличиваем количество людей прошедших тест
                    $testsCount++;
                    $averageTestProgress += intval($testResults['correct_answers'])/intval($testResults['questions_count']);
                }

                $userProgressInfo[$user] = [
                    'read_modules' => $readModules,
                    'test_results' => $testResults,
                ];
            }

            if (count($courseModules) ===0 ){
                $averageCourseProgress = '-';
            } else{
                if (count($allUsers) !== 0 ){
                    $averageCourseProgress /= count($allUsers);
                    $averageCourseProgress = intval($averageCourseProgress*100) . '%';
                } else{
                    $averageCourseProgress = '-';
                }
            }

            if ($testsCount === 0 || !isset($course['test_id'])){
                $averageTestProgress = '-';
            } else{
                $averageTestProgress /= $testsCount;
                $averageTestProgress = intval($averageTestProgress*100) . '%';
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
                'users_progress' =>$userProgressInfo,
                'average_course_progress' => $averageCourseProgress,
                'average_test_progress' => $averageTestProgress,
                'modules_count' => count($courseModules),
                'groups_count' => count($course['user_group']),
                'all_users_count' => count($allUsers),
                'individual_users_count' => count($course['user']),
                'user_from_groups_count' => count($userFromGroups),
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