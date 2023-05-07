<?php

namespace Legacy\Api;
use Legacy\Helper;
use Legacy\Api\User;
use Bitrix\Main\UserTable;

class Auth
{
    public static function Registration($arRequest) {
        global $USER;

        $login = $arRequest['login'];
        $name = $arRequest['name'];
        $lastname = $arRequest['last_name'];
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
                    'user' => User::GetUserInfo()
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
                'user' => User::GetUserInfo()
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