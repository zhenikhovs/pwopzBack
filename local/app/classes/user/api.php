<?php

namespace Legacy\Api;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class User
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

    public static function GetAllUsers() {

        $order = array('sort' => 'asc');
        $tmp = 'sort';
        $arFilter = Array(
            'GROUPS_ID' => 6
        );
        $res = \CUser::GetList($order, $tmp, $arFilter);

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
                'last_name' => $item['LAST_NAME'],
                'email' => $item['EMAIL'],
            ];
        }
        return $arResult;

    }

    public static function GetUserInfo($userID = null) {
        global $USER;

        if(!$userID){
            $userID = $USER->GetID();
        }

        $result = [];

        $user = UserTable::getRow([
            'select' => [
                'ID',
                'LOGIN',
                'NAME',
                'LAST_NAME',
                'EMAIL',
                'PERSONAL_NOTES',
            ],
            'filter' => ['ID' => $userID]
        ]);

        if ($user) {
            //получаю список групп пользователя (там всегда возвращается массив с 2 группой)
            //удаляю вторую группу, превращаю массив в строку
            $userGroupID = implode(array_diff($USER->GetUserGroup($userID), ["2"]));

            //получаю инфу о группе по айди, беру название
            $userGroup = \CGroup::GetByID($userGroupID)->Fetch();

            $userGroupInfo = ['name' => $userGroup['NAME'], 'string_id' => $userGroup['STRING_ID']];

            $result = [
                "id" => $user['ID'],
                "login" => $user['LOGIN'],
                "email" => $user['EMAIL'],
                "name" => $user['NAME'],
                "last_name" => $user['LAST_NAME'],
                "group" => $userGroupInfo,
                "personal_notes" => $user['PERSONAL_NOTES'],
            ];
        }

        return $result;
    }


    public static function UpdateUser($arRequest) {
        global $USER;

        $userID = $arRequest['user_id'];
        $login = $arRequest['login'];
        $name = $arRequest['name'];
        $lastname = $arRequest['last_name'];
        $email = $arRequest['email'];

        $arFields = Array(
            "LOGIN" => $login,
            "NAME" => $name,
            "LAST_NAME" => $lastname,
            "EMAIL" => $email,
        );

        if($USER->Update($userID, $arFields)){
            return Helper::GetResponseApi(200, [
                'user' => self::GetUserInfo()
            ]);
        }
        else{
            return Helper::GetResponseApi(400, [], $USER->LAST_ERROR
            );
        }

    }

    public static function UpdateUserPassword($arRequest)
    {
        global $USER;

        $userID = $arRequest['user_id'];
        $password = $arRequest['password'];
        $confirm_password = $arRequest['confirm_password'];

        $arFields = array(
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $confirm_password,
        );

        if($USER->Update($userID, $arFields)){
            return Helper::GetResponseApi(200, [
                'user' => self::GetUserInfo()
            ]);
        }
        else{
            return Helper::GetResponseApi(400, [], $USER->LAST_ERROR
            );
        }

    }

    //перенести в группы
    public static function GetUserGroupsInfo($user = null) {
        global $USER;

        if(!$user){
            $user = $USER->GetID();
        }
        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Groups, 'ACTIVE'=>'Y', 'PROPERTY_USER'=>$user);

        $arSelect = [
            'ID'
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);

        $arResultUserGroups = [];

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

            $arResultUserGroups[] = $item['ID'];
        }

        return $arResultUserGroups;
    }


    public static function GetUsersFromBX24Info($dealID) {

        $res = \CUser::GetList(
            "personal_notes", "asc",array('PERSONAL_NOTES'=>$dealID));

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

            $arResult[] = $item['ID'];
        }

        return $arResult;
    }
}