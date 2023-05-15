<?php

namespace Legacy\Api;
use Legacy\Api\User;
use Legacy\Api\Course;
use Legacy\Helper;
use Bitrix\Main\UserTable;

class Group
{
    public static function GetGroupsInfo($IDs = []) {

        $arFilter = Array('IBLOCK_ID'=> \Legacy\Config::Groups,
            'ACTIVE'=>'Y',
            'ID' => $IDs
        );

        $arSelect = [
            'ID',
            'NAME',
            'PROPERTY_USER',
        ];

        $res = \CIBlockElement::GetList('ASC', $arFilter, false, false, $arSelect);
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
                'users' => $item['USER'],
            ];
        }
        return $arResult;
    }


}