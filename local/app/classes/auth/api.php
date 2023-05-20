<?php

namespace Legacy\Api;
use Legacy\Helper;
use Legacy\Api\User;
use Bitrix\Main\UserTable;
use Legacy\Api\BX24;


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
        $id_deal = $arRequest['dealid'];
        if(!isset($id_deal)){
            $id_deal = '';
        }
//        $group = explode(',', $arRequest['group']);

        $arFields = Array(
            "LOGIN" => $login,
            "NAME" => $name,
            "LAST_NAME" => $lastname,
            "PASSWORD" => $password,
            "CONFIRM_PASSWORD" => $confirm_password,
            "EMAIL" => $email,
            "GROUP_ID" => ['6'],
            "PERSONAL_NOTES" => $id_deal,
        );


        if ($USER->Add($arFields)) {
            if($id_deal !==''){
                Helper::CurlBitrix24('crm.deal.update', array(
                    'id' => $id_deal,
                    'fields' => array(
                        "UF_CRM_1684234394832" => '1'
                    ),
                    'params' => array("REGISTER_SONET_EVENT" => "Y")
                ));
                 Helper::CurlBitrix24('crm.automation.trigger', array(
                    'target' => 'DEAL_' . $id_deal,
                    'code' => 'ml3bt'
                 ));
                 BX24::UpdateCoursesBX24();
            }

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