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




    public static function GetStatisticsGroups(){
        $courses = Course::GetCoursesInfo();
        $groups = Group::GetGroupsInfo();

        $arResult = [

        ];

        foreach ($groups as $group){
            //информация о курсах группы
            $groupCourses = Course::GetGroupCoursesInfo($group);

            $groupCoursesInfo = [];
            $groupUsersInfo = [];
            $coursesGroupCount = 0;
            $averageGroupCourseProgress = 0;
            $testsGroupCount = 0;
            $averageGroupTestProgress = 0;
            foreach ($groupCourses as $groupCourse ){
                $courseModules = Module::GetCourseModulesInfo($groupCourse['id']);

                $averageCourseProgress = 0;
                $testsCount = 0;
                $averageTestProgress = 0;
                foreach ($group['users'] as $user){
                    //прочитанные модули пользователя
                    $readModules = self::GetReadCourseModulesInfo($groupCourse['id'], $user);

                    //проверка на наличие модулей в курсе
                    if(count($courseModules) !==0 ){
                        $averageCourseProgress += count($readModules)/count($courseModules);
                    }

                    //результаты тестирования пользователя (если нет false)
                    $testResults = Test::GetDoneTestBefore($groupCourse['id'],$user);
                    if($testResults){
                        //увеличиваем количество людей прошедших тест
                        $testsCount++;
                        $averageTestProgress += intval($testResults['correct_answers'])/intval($testResults['questions_count']);
                    }
                }

                if (count($courseModules) ===0 ){
                    $averageCourseProgress = '-';
                } else{
                    if (count($group['users']) !== 0 ){
                        $averageCourseProgress /= count($group['users']);
                        $averageGroupCourseProgress += $averageCourseProgress;
                        $coursesGroupCount++;
                        $averageCourseProgress = intval($averageCourseProgress*100) . '%';
                    } else{
                        $averageCourseProgress = '-';
                    }
                }

                if ($testsCount === 0 || !isset($groupCourse['test_id'])){
                    $averageTestProgress = '-';
                } else{
                    $averageTestProgress /= $testsCount;
                    $averageGroupTestProgress += $averageTestProgress;
                    $testsGroupCount++;
                    $averageTestProgress = intval($averageTestProgress*100) . '%';
                }

                $groupCoursesInfo[] = [
                    'course_name' => $groupCourse['name'],
                    'course_modules_progress' => $averageCourseProgress,
                    'course_test_progress' => $averageTestProgress,
                    'modules_count' => count($courseModules)
                ];
            }


            foreach ($group['users'] as $user){
                $usersInfo = User::GetUserInfo($user);

                $coursesCount = 0;
                $averageCourseProgress = 0;
                $testsCount = 0;
                $averageTestProgress = 0;
                foreach ($groupCourses as $course ) {
                    $courseModules = Module::GetCourseModulesInfo($course['id']);
                    //прочитанные модули пользователя
                    $readModules = self::GetReadCourseModulesInfo($course['id'], $user);
                    //проверка на наличие модулей в курсе
                    if(count($courseModules) !==0 ){
                        $coursesCount++;
                        $averageCourseProgress += count($readModules)/count($courseModules);
                    }

                    //результаты тестирования пользователя (если нет false)
                    $testResults = Test::GetDoneTestBefore($course['id'],$user);
                    if($testResults){
                        //увеличиваем количество пройденных тестов пользователя
                        $testsCount++;
                        $averageTestProgress += intval($testResults['correct_answers'])/intval($testResults['questions_count']);
                    }
                }

                if ($coursesCount ===0 ){
                    $averageCourseProgress = '-';
                } else{
                    $averageCourseProgress /= $coursesCount;
                    $averageCourseProgress = intval($averageCourseProgress*100) . '%';
                }

                if ($testsCount === 0){
                    $averageTestProgress = '-';
                } else{
                    $averageTestProgress /= $testsCount;
                    $averageTestProgress = intval($averageTestProgress*100) . '%';
                }

                $groupUsersInfo[] = [
                    'user_fio' => $usersInfo['name'] . ' ' . $usersInfo['last_name'],
                    'course_modules_progress' => $averageCourseProgress,
                    'course_test_progress' => $averageTestProgress,
                ];

            }

            $averageGroupCourseProgress = count($group['users']) > 0? intval($averageGroupCourseProgress/$coursesGroupCount*100) . '%' : '-';
            $averageGroupTestProgress = count($group['users']) > 0? intval($averageGroupTestProgress/$testsGroupCount*100) . '%' : '-';

            $arResult[$group['name']]=[
                'id' => $group['id'],
                'group_name' => $group['name'],
                'group_courses_count' => count($groupCourses),
                'users_count' => count($group['users']),
                'group_courses_info' => $groupCoursesInfo,
                'course_modules_progress' => $averageGroupCourseProgress,
                'course_test_progress' => $averageGroupTestProgress,
                'group_users_info' =>$groupUsersInfo,
            ];
        }


        return Helper::GetResponseApi(200, [
            'groups_progress' => $arResult
        ]);
    }




    public static function GetStatisticsUsers(){

        $users = User::GetAllUsers();

        $usersCourses = [];
        $usersDetailCourses = [];

        foreach ($users as $user){
            $userCourses = Course::GetUserCoursesInfo($user);

            $coursesCount = 0;
            $averageCourseProgress = 0;
            $testsCount = 0;
            $averageTestProgress = 0;

            $coursesDetail = [];
            foreach ($userCourses as $course ) {
                $courseModules = Module::GetCourseModulesInfo($course['id']);
                //прочитанные модули пользователя
                $readModules = self::GetReadCourseModulesInfo($course['id'], $user);

                $courseProgress = '';
                //проверка на наличие модулей в курсе
                if(count($courseModules) !==0 ){
                    $coursesCount++;
                    $averageCourseProgress += count($readModules)/count($courseModules);
                    $courseProgress = intval(count($readModules)/count($courseModules)*100) . '%';
                } else{
                    $courseProgress = 'Нет модулей';
                }

                $testProgress = '';
                //результаты тестирования пользователя (если нет false)
                $testResults = Test::GetDoneTestBefore($course['id'],$user);
                if($testResults){
                    //увеличиваем количество пройденных тестов пользователя
                    $testsCount++;
                    $averageTestProgress += intval($testResults['correct_answers'])/intval($testResults['questions_count']);
                    $testProgress = intval(intval($testResults['correct_answers'])/intval($testResults['questions_count'])*100) . '%';
                } else {
                    $testProgress = 'Тест не пройден';
                }

                if(!$course['test_id']){
                    $testProgress = 'Тест отсутствует';
                }



                $coursesDetail[] = [
                    'course_info'=> $course,
                    'course_name' => $course['name'],
                    'modules_count' => count($courseModules),
                    'course_progress' => $courseProgress,
                    'test_progress' => $testProgress
                ];
            }

            if ($coursesCount ===0 ){
                $averageCourseProgress = '-';
            } else{
                $averageCourseProgress /= $coursesCount;
                $averageCourseProgress = intval($averageCourseProgress*100) . '%';
            }

            if ($testsCount === 0){
                $averageTestProgress = '-';
            } else{
                $averageTestProgress /= $testsCount;
                $averageTestProgress = intval($averageTestProgress*100) . '%';
            }

            $usersCourses[] = [
                'user_fio' => $user['name'] . ' ' . $user['last_name'],
                'course_count' => count($userCourses),
                'course_modules_progress' => $averageCourseProgress,
                'course_test_progress' => $averageTestProgress,

            ];

            $usersDetailCourses[$user['id']] = $coursesDetail;

        }

        return Helper::GetResponseApi(200, [
            'users_courses' => $usersCourses,
            'users_detail_courses' => $usersDetailCourses,
            'users' => $users,
        ]);
    }


}