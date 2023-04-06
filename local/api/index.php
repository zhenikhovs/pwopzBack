<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Legacy\Config;

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    die();
}
// получаем тело запроса
$sJson = file_get_contents('php://input');
if (!empty($sJson)) {
    $arQuery = json_decode($sJson, true);
}

// if(!$_REQUEST['debug']){

//     if($arQuery['apikey'] != Config::ApiKey){
//         http_response_code(200);
//         print_r(mb_strtolower(json_encode(['error' => 'Не указан API KEY'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)));
//         die();
//     }

// }

try {
    if (empty($_GET['method'])) {
        throw new Exception('Пустой method');
    }

    $arMethod = explode('.', $_GET['method']);
    if (empty($arMethod[0]) and empty($arMethod[1])) {
        throw new Exception('Неполный method');
    }
    $function = $arMethod[count($arMethod) - 1];
    unset($arMethod[count($arMethod) - 1]);
    $class = 'Legacy\Api\\' . implode('\\', $arMethod);

    if (!class_exists($class)) {
        throw new Exception('Класс ' . $class . ' не существует');
    } elseif (!method_exists($class, $function)) {
        throw new Exception('Функция ' . $function . ' в классе ' . $class . ' не существует');
    }

    // начало выполнения скрипта загрузки
    $start = microtime(true);

    $arParams = [];
    if (!empty($arQuery) and is_array($arQuery)) {
        $arParams = $arQuery;
    }
    $arReturn = $class::$function($arParams);

    if (empty($arReturn['status']) or empty($arReturn['code'])) {
        throw new Exception('Метод не смог вернуть ответ');
    }

    // конец выполнения скрипта
    $end = round(microtime(true) - $start, 4) . ' сек.';
    $arReturn['time_loading'] = 'Время выполнения скрипта ' . $end;

    http_response_code($arReturn['code']);

	/**
 * Все ключи многомерных массивов PHP конвертируются в нижний регистр || верхний регистр
   * @param $ array // данные
   * @param int $ case // CASE_LOWER нижний регистр, CASE_UPPER верхний регистр
 * @return array
 */
function array_change_key_case_all(&$array, $case = CASE_LOWER)
{
    $array = array_change_key_case($array, $case);
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            array_change_key_case_all($array[$key], $case);
        }
    }

    return $array;
}

	print_r(json_encode(array_change_key_case_all($arReturn), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));
} catch (Exception $e) {
    http_response_code(200);
    print_r(mb_strtolower(json_encode(['error' => $e->getMessage()], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)));
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
die();
