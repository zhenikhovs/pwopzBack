<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Legacy\Config;
use Legacy\Helper;
use Legacy\Api\BX24;
use Legacy\Api\Course;
use Legacy\Api\User;

addUsersToCourse($_REQUEST);
function addUsersToCourse($data) {
    $dealID = mb_substr($data['document_id'][2],5);

    $usersFromBX24 = User::GetUsersFromBX24Info($dealID);

    $courseIDBX24 = BX24::GetDealBX24Info($dealID)['UF_CRM_1684238718'];

    $courseB24 = BX24::GetListCourseBX24Info();

    $courseNameBX34 = '';
    foreach ($courseB24 as $course){
        if($course['ID'] === $courseIDBX24){
            $courseNameBX34 = $course['VALUE'];
        }
    }

    [$courseName, $courseID ]= explode('><',$courseNameBX34);

    $courseUsers = Course::GetCourseInfo($courseID);
    $arLoadProductArray = Array(
        'PROPERTY_VALUES'=>[
            'USER'=>array_merge($courseUsers['users'], $usersFromBX24),
            'TEST'=>$courseUsers['test'],
            'DESCRIPTION'=>$courseUsers['description'],
            'USER_GROUP'=>$courseUsers['groups'],
        ]
    );

    $el = new \CIBlockElement;
    $el->Update($courseID, $arLoadProductArray);


}
