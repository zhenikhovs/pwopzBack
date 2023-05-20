<?php

namespace Legacy\Api;
use Legacy\Helper;
use Legacy\Api\User;
use Bitrix\Main\UserTable;

class BX24
{
    public static function GetDealBX24Info ($dealID) {
        $res = Helper::CurlBitrix24('crm.deal.get',
            array(
                'id' => $dealID
            )
        )['result'];
        return $res;
    }

    public static function UpdateCoursesBX24() {
        $newCourses = [];
        $courses = Course::GetCoursesInfo();
        foreach ($courses as $course){
            $newCourses[] = ["VALUE" => $course['name'] . '><' . $course['id']];
        }

        $toDelete = [];
        $coursesFromB24 = self::GetListCourseBX24Info();
        foreach ($coursesFromB24 as $course){
            $toDelete[] = [
                "ID" => $course['ID'],
                "DEL" => "Y"
            ];
        }

        $fields =
            array(
                "LIST" => array_merge(
                    $toDelete,
                    $newCourses
                )
            );

        Helper::CurlBitrix24('crm.deal.userfield.update',
            array(
                'id' => '1361',
                'fields' => $fields,
            )
        );
    }

    public static function GetListCourseBX24Info () {
        $res = Helper::CurlBitrix24('crm.deal.userfield.get',
            array(
                'id' => '1361'
            )
        );
        return $res['result']['LIST'];
    }

    public static function GetListCourseBX24 () {
        $res = Helper::CurlBitrix24('crm.deal.userfield.get',
            array(
                'id' => '1361'
            )
        );
        return Helper::GetResponseApi(200, [
            'courses' => $res['result']['LIST']
        ]);
    }


}