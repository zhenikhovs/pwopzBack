<?php

namespace Legacy\Api;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Auth
{
    public static function GetUser() {
        global $USER;

        if ($USER->IsAuthorized()) {
            return Helper::GetResponseApi(200, [
                'user' => self::GetUserInfo()
            ]);
        }
        else{
            return Helper::GetResponseApi(200, []);
        }
    }

    public static function GetUserInfo() {
        global $USER;

        $result = [];

        $user = UserTable::getRow([
            'select' => [
                'ID',
                'LOGIN',
                'NAME',
                'LAST_NAME',
                'EMAIL'
            ],
            'filter' => ['ID' => $USER->GetID()]
        ]);

        if ($user) {
            //получаю список групп пользователя (там всегда возвращается массив с 2 группой)
            //удаляю вторую группу, превращаю массив в строку
            $userGroupID = implode(array_diff($USER->GetUserGroup($USER->GetID()), ["2"]));

            //получаю инфу о группе по айди, беру название
            $userGroup = \CGroup::GetByID($userGroupID)->Fetch();

            $userGroupInfo = ['name' => $userGroup['NAME'], 'string_id' => $userGroup['STRING_ID']];

            $result = [
                "id" => $user['ID'],
                "login" => $user['LOGIN'],
                "email" => $user['EMAIL'],
                "name" => $user['NAME'],
                "last_name" => $user['LAST_NAME'],
                "group" => $userGroupInfo
            ];
        }

        return $result;
    }



    public static function Registration($arRequest) {
        global $USER;

        $login = $arRequest['login'];
        $name = $arRequest['name'];
        $lastname = $arRequest['lastname'];
        $password = $arRequest['password'];
        $confirm_password = $arRequest['confirm_password'];
        $email = $arRequest['email'];
        $group = explode(',', $arRequest['group']);

        $arFields = Array(
            "LOGIN" => $login,
            "NAME" => $name,
            "LAST_NAME" => $lastname,
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $confirm_password,
            "EMAIL" => $email,
            "GROUP_ID" => $group
        );


        if ($USER->Add($arFields)) {
            $arAuthResult = $USER->Login($login, $password);
            if ($USER->IsAuthorized()) {
                return Helper::GetResponseApi(200, [
                    'user' => self::GetUserInfo()
                ]);
            }
            else {
                return Helper::GetResponseApi(400, [], $arAuthResult["MESSAGE"]);
            }
        } else {
            return Helper::GetResponseApi(400,[], $USER->LAST_ERROR);
        }
    }

    public static function Login($arRequest) {
        global $USER;
        
        $login = $arRequest['login'];
        $password = $arRequest['password'];
        $arAuthResult = $USER->Login($login, $password);

        if ($USER->IsAuthorized()) {
            return Helper::GetResponseApi(200, [
                'user' => self::GetUserInfo()
            ]);
        }
        else {
            return Helper::GetResponseApi(400, [], $arAuthResult["MESSAGE"]);
        }
    }

    public static function Logout() {
       global $USER;
       $USER->Logout();
       return Helper::GetResponseApi(200, []);
    }

}