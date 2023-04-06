<?php

namespace Legacy;

use CPHPCache;
use Legacy\Content as ContentGeneral;
use Bitrix\Main\Loader;

Loader::includeModule("highloadblock");

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class Helper
{
//     формирование ответа для api
    public static function GetResponseApi(int $iCode, array $arResult = []): array
    {
        return [
            'status' => $iCode == 200 ? 'success' : 'error',
            'code' => $iCode,
            'result' => $arResult
        ];
    }


    public static function GetResponseAjax($code, $data)
    {

        if ($code) {
            $code = 200;
        } else {
            $code = 422;
        }

        $arData = $data;
        $arData["code"] = $code;
        $arData["status"] = $data['message'];

        return $arData;
    }

    // получаем/генерируем кэш
    // $time время жизни кэша
    // $cache название кэша
    // $callback функция по которой получаем результат
    // $arCallbackParams параметры которые передаем в функцию
    public static function GetCacheResult(int $time, string $cache, string $callback, array $arCallbackParams = [])
    {
        $result = [];
        $obCache = new CPHPCache();
        $cachePath = '/' . SITE_ID . '/' . $cache;
        if ($obCache->InitCache($time, $cache, $cachePath)) {
            $vars = $obCache->GetVars();
            $result = $vars['result'];
        } elseif ($obCache->StartDataCache()) {
            $result = $callback($arCallbackParams);
            if (!empty($result)) {
                $obCache->EndDataCache(['result' => $result]);
            } else {
                $obCache->EndDataCache();
            }
        }

        return $result;
    }

    public static function GetUpperFirst($str, $encoding = 'UTF8'): string
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
    }

    // получаем/назначаем домен сайта (для ссылок на файлы,canonical и тд, везде где нужен путь с доменов)
    public static function GetDomain(): string
    {
        //return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
        return Config::SiteDomen;
    }

    //склонение слов
    public static function Declension(int $number, $after)
    {
        $cases = array(2, 0, 1, 1, 1, 2);
        return $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    // получаем текущий урл и его части
    public static function GetUrlCurrent(): array
    {
        global $APPLICATION;

        $url = $APPLICATION->GetCurPage(); //получить текущий url
        $urlBroken = explode('/', $url);
        $urlBroken = array_diff($urlBroken, ['']);

        return [
            'full' => $url,
            'get' => !empty($_GET) ? $_GET : false,
            'broken' => !empty($urlBroken) ? $urlBroken : false
        ];
    }

    // форматируем исходную цену в красивый формат
    public static function FormatPrice($price, $space = ' ')
    {
        if (empty($price)) {
            return false;
        }

        $priceExp = explode('.', $price);
        if (empty($priceExp[1]) or $priceExp[1] == '00') {
            $priceFormat = number_format($price, 0, ',', $space) . $space . '₽';
        } else {
            $priceFormat = number_format($price, 2, ',', $space) . $space . '₽';
        }

        return $priceFormat;
    }

    // форматируем исходную цену в короткую миллионы
    public static function FormatPriceMillion($price, $space = ' ')
    {
        if (empty($price)) {
            return false;
        }

        return number_format($price / 1000000, 2, ',', $space) . $space . 'млн' . $space . '₽';
    }

    // форматируем исходную цену в короткую тысячи
    public static function FormatPriceThousand($price, $space = ' ')
    {
        if (empty($price)) {
            return false;
        }

        return number_format($price / 1000, 0, ',', $space) . $space . 'тыс.' . $space . '₽';
    }

    // форматируем исходную площадь
    public static function FormatArea($area, $space = ' ')
    {
        if (empty($area)) {
            return false;
        }

        $areaExp = explode('.', $area);
        if (empty($areaExp[1]) or $areaExp[1] == '00') {
            $areaFormat = number_format($area, 0, '.', '') . $space . 'м²';
        } else {
            $areaFormat = number_format($area, 2, '.', '') . $space . 'м²';
        }

        return $areaFormat;
    }

    // получаем все заголовки браузера
    public static function GetRequestHeaders(): array
    {
        return apache_request_headers();
    }

    // проверяем поддерживается ли webp
    public static function isSupportWebp(): bool
    {
        $headers = self::GetRequestHeaders();
        //$webp = false;
        //if (!empty($headers['Accept']) or !empty($headers['accept']) or !empty($headers['ACCEPT'])) {
        //if (strpos($headers['Accept'], 'image/webp') or strpos($headers['accept'], 'image/webp') or strpos($headers['ACCEPT'], 'image/webp')) {
        $webp = true;
        //}
        //}

        return $webp;
    }


    // Получаю класс апартаментов
    public static function getClassApartments(): array
    {

        $hlbl = 1; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
           "select" => array("*"),
           "order" => array("ID" => "ASC"),
           "filter" => array()  // Задаем параметры фильтра выборки
        ));

        while($arData = $rsData->Fetch()){
           $arResult[$arData['UF_XML_ID']] = $arData;
        }

        return $arResult;
    }

    // webp генерация
    public static function MakeWebp(string $src)
    {
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $src)) {
            return $src;
        }

        $imgNew = '';
        $imgPathNew = '';
        if (function_exists('imagewebp')) {
            $imgPath = str_ireplace(['.jpg', '.jpeg', '.png'], '.webp', $src);
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $imgPath)) {
                if (stripos($src, '.png')) {
                    $imgNew = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $src);
                    $imgPathNew = str_ireplace('.png', '.webp', $src);
                } elseif (stripos($src, '.jpg') !== false || stripos($src, '.jpeg') !== false) {
                    $imgNew = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $src);
                    $imgPathNew = str_ireplace(array('.jpg', '.jpeg'), '.webp', $src);
                }

                if (!empty($imgNew)) {
                    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $imgPathNew)) {
                        imagewebp($imgNew, $_SERVER['DOCUMENT_ROOT'] . $imgPathNew, 85);
                    }

                    imagedestroy($imgNew);
                }
            } else {
                $imgPathNew = $imgPath;
            }
        }

        return $imgPathNew;
    }

    public static function GetFilePath(int $id): string
    {
        return self::GetDomain() . \CFile::GetPath($id);
    }

    public static function GetFileArray(int $id): array
    {
        $arFile = \CFile::GetFileArray($id);
        if (empty($arFile)) {
            return [];
        }

        $arFile['SRC'] = self::GetDomain() . $arFile['SRC'];
        $arFile['TYPE'] = explode('.', $arFile['FILE_NAME'])[1];
        $arFile['SIZE'] = \CFile::FormatSize($arFile['FILE_SIZE']);

        return $arFile;
    }

    public static function GetImagePath(int $id, int $width, int $height, $type = BX_RESIZE_IMAGE_PROPORTIONAL_ALT, bool $waterMark = false, int $quality = 95): array
    {
        if ($waterMark === true) {
            $arWaterMark = [
                [
                    'name' => 'watermark', // Не менять и не убирать, не работает без этого ¯\(°_o)/¯
                    'position' => 'topleft', // Положение
                    'type' => 'image',
                    'size' => 'real',
                    'alpha_level' => 20,
                    'file' => $_SERVER['DOCUMENT_ROOT'] . '/local/assets/images/water_mark.png' // Путь к картинке марки
                ]
            ];
        } else {
            $arWaterMark = false;
        }

        $arImg = \CFile::ResizeImageGet($id, ['width' => $width, 'height' => $height], $type, true, $arWaterMark, false, $quality);
        $imgWebp = Helper::MakeWebp($arImg['src']);

        $arImg['src_root'] = $arImg['src'];
        $arImg['src'] = self::GetDomain() . $arImg['src'];
        if (!empty($imgWebp)) {
            $arImg['src_webp'] = self::GetDomain() . $imgWebp;
        }

        return $arImg;
    }

    // форматирование телефона в единый формат для сайта 70001112233
    public static function FormatPhone($phone)
    {
        if (empty($phone)) {
            return false;
        }

        $pr = substr(trim($phone), 0, 2);
        if ($pr == '+7' or $pr == '79' or $pr == '89') {

            $str = ['+', '_', '-', '(', ')', ' '];
            $phone = str_replace($str, '', $phone);
            $phone = substr_replace($phone, '7', 0, 1);
            if (strlen($phone) != 11) {
                return false;
            }
        } else {
            $phone = preg_replace('/[^0-9]/', '', $phone);
        }

        return $phone;
    }

    // телефон в красивый формат 70001112233 в +7(000)111-22-33
    public static function FormatPhoneBeautiful($phone)
    {
        if (empty($phone)) {
            return false;
        }

        $phone = preg_replace("[^0-9]", '', $phone);
        if (strlen($phone) < 11) {
            return false;
        }

        $code = substr($phone, 0, -10);
        $phone = mb_substr($phone, -10);

        if ($code == '7' or $code == '8') {
            $code = '+7';
        } else {
            $code = '+' . $code;
        }

        $area = substr($phone, 0, 3);
        $prefix = substr($phone, 3, 3);
        $number = substr($phone, 6, 4);

        return $code . ' (' . $area . ') ' . $prefix . '-' . substr($number, 0, 2) . substr($number, 2, 4);
    }

    public static function GoogleRecaptcha(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        //Сюда пишем СЕКРЕТНЫЙ КЛЮЧ, который нам присвоил гугл
        $secret = '6LcDuoMcAAAAAA4k_FigxvCpTPOUTTpAfawp6jiP';
        //Формируем utl адрес для запроса на сервер гугла
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $token . '&remoteip=' . $_SERVER['REMOTE_ADDR'];
        //Инициализация и настройка запроса
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        //Выполняем запрос и получается ответ от сервера гугл
        $result = curl_exec($curl);
        curl_close($curl);
        //Ответ приходит в виде json строки, декодируем ее
        $result = json_decode($result, true);

        //Смотрим на результат
        if (empty($result['success'])) {
            return false;
        }

        return true;
    }

    public static function FormatContentSimple(array $arContent): array
    {
        if (empty($arContent)) {
            return [];
        }

        $arBlocks = [];
        foreach ($arContent['blocks'] as $arBlock) {
            switch ($arBlock['name']) {
                case 'htag':
                    if (!empty($arBlock['value'])) {
                        $arBlocks['blocks'][] = [
                            'type' => 'header',
                            'value' => [
                                'tag' => $arBlock['type'],
                                'text' => $arBlock['value']
                            ],
                        ];
                    }
                    break;
                case 'text':
                    if (!empty($arBlock['value'])) {
                        $arBlocks['blocks'][] = [
                            'type' => 'text',
                            'value' => $arBlock['value'],
                        ];
                    }
                    break;
                case 'image':
                    if (!empty($arBlock['file']['ID'])) {
                        $arImg = Helper::GetImagePath($arBlock['file']['ID'], 935, 935, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                        if (!empty($arBlock['desc'])) {
                            $arImg['description'] = $arBlock['desc'];
                        }

                        if (!empty($arImg)) {
                            $arBlocks['blocks'][] = [
                                'type' => 'image',
                                'value' => $arImg,
                            ];
                        }
                    }
                    break;
                case 'files':
                    if (!empty($arBlock['files'])) {
                        $arFiles = [];
                        foreach ($arBlock['files'] as $item) {
                            if (!empty($item['file']['ID'])) {
                                $file = Helper::GetFileArray($item['file']['ID']);
                                if (!empty($file['SRC'])) {
                                    $arFiles[] = [
                                        'title' => !empty($item['desc']) ? $item['desc'] : $file['ORIGINAL_NAME'],
                                        'src' => $file['SRC'],
                                        'type' => $file['TYPE'],
                                        'size' => $file['SIZE'],
                                    ];
                                }
                            }
                        }

                        if (!empty($arFiles)) {
                            $arBlocks['blocks'][] = [
                                'type' => 'files',
                                'value' => $arFiles,
                            ];
                        }
                    }
                    break;
                case 'video':
                    if (!empty($arBlock['url'])) {
                        parse_str(parse_url($arBlock['url'], PHP_URL_QUERY), $arUrl);
                        if (!empty($arUrl['v'])) {
                            $arPrev = [];
                            if (!empty($arBlock['preview']['file']['ID'])) {
                                $arPrev = Helper::GetImagePath($arBlock['preview']['file']['ID'], 935, 500, BX_RESIZE_IMAGE_EXACT);
                            }

                            $arBlocks['blocks'][] = [
                                'type' => 'video',
                                'value' => 'https://www.youtube.com/embed/' . $arUrl['v'],
                                'preview' => !empty($arPrev) ? $arPrev : false
                            ];
                        }
                    }
                    break;
                case 'gallery':
                    if (!empty($arBlock['images'])) {
                        $arSlider = [];
                        foreach ($arBlock['images'] as $item) {
                            if (!empty($item['file']['ID'])) {
                                $img = Helper::GetImagePath($item['file']['ID'], 935, 935, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                                if (!empty($item['desc'])) {
                                    $img['description'] = $item['desc'];
                                }
                                if (!empty($img['src'])) {
                                    $arSlider[] = $img;
                                }
                            }
                        }

                        if (!empty($arSlider)) {
                            $arBlocks['blocks'][] = [
                                'type' => 'slider',
                                'value' => $arSlider,
                            ];
                        }
                    }
                    break;
            }
        }

        return $arBlocks;
    }

    // Дата парсинг для от и до
    public static function surWireDate($date)
    {
        $date = explode('.',substr($date,0,5));

        $mass_mesyac = [
            '01' => 'января',
            '02' => 'февраля',
            '03' => 'марта',
            '04' => 'апреля',
            '05' => 'мая',
            '06' => 'июня',
            '07' => 'июля',
            '08' => 'августа',
            '09' => 'сентября',
            '10' => 'октября',
            '11' => 'ноября',
            '12' => 'декабря',
        ];

        $date = $date[0].' '.$mass_mesyac[$date[1]];

        return $date;
    }

    public static function FormatContentFull(array $arContent): array
    {
        if (empty($arContent)) {
            return [];
        }

        $arBlocks = [];
        foreach ($arContent['blocks'] as $arBlock) {
            switch ($arBlock['name']) {
                case 'htag':
                    if (!empty($arBlock['value'])) {
                        $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                            'type' => 'header',
                            'value' => [
                                'tag' => $arBlock['type'],
                                'text' => $arBlock['value']
                            ],
                        ];
                    }
                    break;
                case 'text':
                    if (!empty($arBlock['value'])) {
                        $arBlock['value'] = str_replace('href="#call_request"', 'href="#" data-modal-trigger="callme"', $arBlock['value']);

                        $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                            'type' => 'text',
                            'value' => $arBlock['value'],
                        ];
                    }
                    break;
                case 'files':
                    if (!empty($arBlock['files'])) {
                        $arFiles = [];
                        foreach ($arBlock['files'] as $item) {
                            if (!empty($item['file']['ID'])) {
                                $file = Helper::GetFileArray($item['file']['ID']);
                                if (!empty($file['SRC'])) {
                                    $arFiles[] = [
                                        'title' => !empty($item['desc']) ? $item['desc'] : $file['ORIGINAL_NAME'],
                                        'src' => $file['SRC'],
                                        'type' => $file['TYPE'],
                                        'size' => $file['SIZE'],
                                    ];
                                }
                            }
                        }

                        if (!empty($arFiles)) {
                            $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                'type' => 'files',
                                'value' => $arFiles,
                            ];
                        }
                    }
                    break;
                case 'video':
                    if (!empty($arBlock['url'])) {
                        parse_str(parse_url($arBlock['url'], PHP_URL_QUERY), $arUrl);
                        if (!empty($arUrl['v'])) {
                            $arPrev = [];
                            if (!empty($arBlock['preview']['file']['ID'])) {
                                $arPrev = Helper::GetImagePath($arBlock['preview']['file']['ID'], 935, 500, BX_RESIZE_IMAGE_EXACT);
                            }

                            $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                'type' => 'video',
                                'value' => 'https://www.youtube.com/embed/' . $arUrl['v'],
                                'preview' => !empty($arPrev) ? $arPrev : false
                            ];
                        }
                    }
                    break;
                case 'lists':
                    if (!empty($arBlock['elements'])) {
                        $arValues = [];
                        foreach ($arBlock['elements'] as $arItem) {
                            $arItem['text'] = explode('#', $arItem['text']);
                            $arValues[] = [
                                'title' => $arItem['text'][0],
                                'text' => !empty($arItem['text'][1]) ? $arItem['text'][1] : false
                            ];
                        }

                        $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                            'type' => 'list',
                            'value' => $arValues,
                        ];
                    }
                    break;
                case 'accordion':
                    if (!empty($arBlock['items'])) {
                        $arValues = [];
                        foreach ($arBlock['items'] as $arItem) {
                            if (!empty($arItem['blocks'])) {
                                $arValue = [];
                                foreach ($arItem['blocks'] as $arItemBlock) {
                                    if ($arItemBlock['name'] == 'image') {
                                        if (!empty($arItemBlock['file']['ID'])) {
                                            $img = Helper::GetImagePath($arItemBlock['file']['ID'], 860, 860, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                                            if (!empty($arItemBlock['desc'])) {
                                                $img['description'] = $arItemBlock['desc'];
                                            }
                                            if (!empty($img['src'])) {
                                                $arValue[] = [
                                                    'type' => 'image',
                                                    'value' => $img,
                                                ];
                                            }
                                        }
                                    } elseif ($arItemBlock['name'] == 'text') {
                                        $arValue[] = [
                                            'type' => 'text',
                                            'value' => $arItemBlock['value'],
                                        ];
                                    }
                                }

                                if (!empty($arValue)) {
                                    $arValues[] = [
                                        'title' => $arItem['title'],
                                        'blocks' => $arValue
                                    ];
                                }
                            }
                        }

                        if (!empty($arValues)) {
                            $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                'type' => 'accordion',
                                'value' => $arValues,
                            ];
                        }
                    }
                    break;
                case 'image':
                    if (!empty($arBlock['file']['ID'])) {
                        $arImg = [];
                        $arImg['default'] = Helper::GetImagePath($arBlock['file']['ID'], 935, 935, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                        $arImg['shadow'] = Helper::GetImagePath($arBlock['file']['ID'], 862, 862, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                        if (!empty($arBlock['desc'])) {
                            $arImg['description'] = $arBlock['desc'];
                        }

                        if (!empty($arImg['default'])) {
                            $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                'type' => 'image',
                                'value' => $arImg,
                            ];
                        }
                    }
                    break;
                case 'gallery':
                    if (!empty($arBlock['images'])) {
                        $arSlider = [];
                        foreach ($arBlock['images'] as $item) {
                            if (!empty($item['file']['ID'])) {
                                $img = Helper::GetImagePath($item['file']['ID'], 935, 935, BX_RESIZE_IMAGE_PROPORTIONAL_ALT);
                                if (!empty($item['desc'])) {
                                    $img['description'] = $item['desc'];
                                }
                                if (!empty($img['src'])) {
                                    $arSlider[] = $img;
                                }
                            }
                        }

                        if (!empty($arSlider)) {
                            $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                'type' => 'slider',
                                'value' => $arSlider,
                            ];
                        }
                    }
                    break;
                case 'iblock_elements':
                    if (!empty($arBlock['element_ids']) and !empty($arBlock['iblock_id'])) {
                        if ($arBlock['iblock_id'] == Config::BlockIconIBlock) {
                            $arItems = ContentGeneral::GetIconBlock($arBlock['element_ids']);

                            if (!empty($arItems)) {
                                $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                    'type' => 'blocks',
                                    'value' => [
                                        'block' => 'icon',
                                        'items' => $arItems
                                    ],
                                ];
                            }
                        } elseif ($arBlock['iblock_id'] == Config::BlockIconLinkIBlock) {
                            $arItems = ContentGeneral::GetIconLinkBlock($arBlock['element_ids']);

                            if (!empty($arItems)) {
                                $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                    'type' => 'blocks',
                                    'value' => [
                                        'block' => 'link',
                                        'items' => $arItems
                                    ],
                                ];
                            }
                        } elseif ($arBlock['iblock_id'] == Config::PartnersIBlock) {
                            $arItems = ContentGeneral::GetPartnersBlock($arBlock['element_ids']);

                            if (!empty($arItems)) {
                                $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                    'type' => 'blocks',
                                    'value' => [
                                        'block' => 'partners',
                                        'items' => $arItems
                                    ],
                                ];
                            }
                        } elseif ($arBlock['iblock_id'] == Config::TableBankIBlock) {
                            $arTables = [];
                            $arSelect = ['ID', 'PROPERTY_DATA'];
                            $arFilter = ['IBLOCK_ID' => Config::TableBankIBlock, 'ID' => $arBlock['element_ids']];
                            $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
                            while ($ob = $res->GetNextElement()) {
                                $arTables[] = $ob->GetFields();
                            }

                            if (!empty($arTables)) {
                                $tablesHTML = '';
                                foreach ($arTables as $arTable) {
                                    $tableHTML = '';
                                    if (!empty($arTable['PROPERTY_DATA_ENUM_ID'])) {
                                        switch ($arTable['PROPERTY_DATA_ENUM_ID']) {
                                            case 35:
                                                $arBanks = [];
                                                $arSelect = [
                                                    'ID',
                                                    'NAME',
                                                    'PROPERTY_LOGO',
                                                    'PROPERTY_CONTRIBUTION_FIRST_MATERNITY'
                                                ];
                                                $arFilter = ['IBLOCK_ID' => Config::BankIBlock, 'ACTIVE' => 'Y', '!PROPERTY_CONTRIBUTION_FIRST_MATERNITY' => false];
                                                $res = \CIBlockElement::GetList(['SORT' => 'ASC'], $arFilter, false, false, $arSelect);
                                                while ($ob = $res->GetNextElement()) {
                                                    $arFields = $ob->GetFields();
                                                    $arFields['NAME'] = $arFields['~NAME'];
                                                    if (!empty($arFields['PROPERTY_LOGO_VALUE'])) {
                                                        $arFields['LOGO'] = Helper::GetFilePath($arFields['PROPERTY_LOGO_VALUE']);
                                                    }

                                                    $arBanks[] = $arFields;
                                                }

                                                if (empty($arBanks)) {
                                                    break;
                                                }

                                                ob_start();
                                                ?>
                                                <table>
                                                    <thead>
                                                    <tr>
                                                        <td>Банк</td>
                                                        <td colspan="2">Первый взнос</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <? foreach ($arBanks as $arBank) { ?>
                                                        <tr>
                                                            <td><? if (!empty($arBank['LOGO'])) { ?><img src="<?= $arBank['LOGO'] ?>" alt="<?= $arBank['NAME'] ?>"><? } ?><?= $arBank['NAME'] ?></td>
                                                            <td><?= $arBank['~PROPERTY_CONTRIBUTION_FIRST_MATERNITY_VALUE'] ?></td>
                                                            <td><small><?= $arBank['~PROPERTY_CONTRIBUTION_FIRST_MATERNITY_DESCRIPTION'] ?></small></td>
                                                        </tr>
                                                    <? } ?>
                                                    </tbody>
                                                </table>
                                                <?
                                                $tableHTML = ob_get_contents();
                                                ob_end_clean();

                                                break;
                                            case 36:
                                            case 38:
                                                $arBanks = [];
                                                $arSelect = [
                                                    'ID',
                                                    'NAME',
                                                    'PROPERTY_LOGO',
                                                    'PROPERTY_OWN_MONEY_LC_1',
                                                    'PROPERTY_CASE_MORTGAGE_LC_1',
                                                    'PROPERTY_OWN_MONEY_LC_2',
                                                    'PROPERTY_CASE_MORTGAGE_LC_2',
                                                ];
                                                $arFilter = [
                                                    'IBLOCK_ID' => Config::BankIBlock, 'ACTIVE' => 'Y',
                                                    [
                                                        'LOGIC' => 'OR',
                                                        ['!PROPERTY_OWN_MONEY_LC_1' => false],
                                                        ['!PROPERTY_OWN_MONEY_LC_2' => false],
                                                    ]
                                                ];
                                                $res = \CIBlockElement::GetList(['SORT' => 'ASC'], $arFilter, false, false, $arSelect);
                                                while ($ob = $res->GetNextElement()) {
                                                    $arFields = $ob->GetFields();
                                                    $arFields['NAME'] = $arFields['~NAME'];
                                                    if (!empty($arFields['PROPERTY_LOGO_VALUE'])) {
                                                        $arFields['LOGO'] = Helper::GetFilePath($arFields['PROPERTY_LOGO_VALUE']);
                                                    }

                                                    $arBanks[] = $arFields;
                                                }

                                                if (empty($arBanks)) {
                                                    break;
                                                }

                                                ob_start();
                                                ?>
                                                <table>
                                                    <thead>
                                                    <tr>
                                                        <td>Банк</td>
                                                        <td>Открытие аккредитива на собственные денежные средства<br>(= первоначальному взносу)</td>
                                                        <td>Открытие аккредитива в случае ипотечной сделки<br>(привлечение заёмных средств)</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <? foreach ($arBanks as $arBank) { ?>
                                                        <tr>
                                                            <td><? if (!empty($arBank['LOGO'])) { ?><img src="<?= $arBank['LOGO'] ?>" alt="<?= $arBank['NAME'] ?>"><? } ?><?= $arBank['NAME'] ?></td>
                                                            <td><?= !empty($arBank['~PROPERTY_OWN_MONEY_LC_1_VALUE']) ? $arBank['~PROPERTY_OWN_MONEY_LC_1_VALUE'] . (!empty($arBank['~PROPERTY_OWN_MONEY_LC_1_DESCRIPTION']) ? '<br><small>' . $arBank['~PROPERTY_OWN_MONEY_LC_1_DESCRIPTION'] . '</small>' : '') : '—' ?></td>
                                                            <td><?= !empty($arBank['~PROPERTY_OWN_MONEY_LC_2_VALUE']) ? $arBank['~PROPERTY_OWN_MONEY_LC_2_VALUE'] . (!empty($arBank['~PROPERTY_OWN_MONEY_LC_2_DESCRIPTION']) ? '<br><small>' . $arBank['~PROPERTY_OWN_MONEY_LC_2_DESCRIPTION'] . '</small>' : '') : '—' ?></td>
                                                        </tr>
                                                    <? } ?>
                                                    </tbody>
                                                </table>
                                                <?
                                                $tableHTML = ob_get_contents();
                                                ob_end_clean();

                                                break;
                                            case 37:
                                                $arBanks = [];
                                                $arSelect = [
                                                    'ID',
                                                    'NAME',
                                                    'PROPERTY_LOGO',
                                                    'PROPERTY_CASH_COMMISSION',
                                                    'PROPERTY_OFFICE_COMMISSION',
                                                    'PROPERTY_PERSONAL_ACCOUNT_COMMISSION',
                                                ];
                                                $arFilter = [
                                                    'IBLOCK_ID' => Config::BankIBlock, 'ACTIVE' => 'Y',
                                                    [
                                                        'LOGIC' => 'OR',
                                                        ['!PROPERTY_CASH_COMMISSION' => false],
                                                        ['!PROPERTY_OFFICE_COMMISSION' => false],
                                                        ['!PROPERTY_PERSONAL_ACCOUNT_COMMISSION' => false],
                                                    ]
                                                ];
                                                $res = \CIBlockElement::GetList(['SORT' => 'ASC'], $arFilter, false, false, $arSelect);
                                                while ($ob = $res->GetNextElement()) {
                                                    $arFields = $ob->GetFields();
                                                    $arFields['NAME'] = $arFields['~NAME'];
                                                    if (!empty($arFields['PROPERTY_LOGO_VALUE'])) {
                                                        $arFields['LOGO'] = Helper::GetFilePath($arFields['PROPERTY_LOGO_VALUE']);
                                                    }

                                                    $arBanks[] = $arFields;
                                                }

                                                if (empty($arBanks)) {
                                                    break;
                                                }

                                                ob_start();
                                                ?>
                                                <table>
                                                    <thead>
                                                    <tr>
                                                        <td>Банк</td>
                                                        <td>Наличными в кассе банка</td>
                                                        <td>В офисе банка со счёта физ.лица</td>
                                                        <td>Через личный кабинет в интернет-Банке</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <? foreach ($arBanks as $arBank) { ?>
                                                        <tr>
                                                            <td><? if (!empty($arBank['LOGO'])) { ?><img src="<?= $arBank['LOGO'] ?>" alt="<?= $arBank['NAME'] ?>"><? } ?><?= $arBank['NAME'] ?></td>
                                                            <td><?= !empty($arBank['~PROPERTY_CASH_COMMISSION_VALUE']) ? $arBank['~PROPERTY_CASH_COMMISSION_VALUE'] . (!empty($arBank['~PROPERTY_CASH_COMMISSION_DESCRIPTION']) ? '<br><small>' . $arBank['~PROPERTY_CASH_COMMISSION_DESCRIPTION'] . '</small>' : '') : '—' ?></td>
                                                            <td><?= !empty($arBank['~PROPERTY_OFFICE_COMMISSION_VALUE']) ? $arBank['~PROPERTY_OFFICE_COMMISSION_VALUE'] . (!empty($arBank['~PROPERTY_OFFICE_COMMISSION_DESCRIPTION']) ? '<br><small>' . $arBank['~PROPERTY_OFFICE_COMMISSION_DESCRIPTION'] . '</small>' : '') : '—' ?></td>
                                                            <td><?= !empty($arBank['~PROPERTY_PERSONAL_ACCOUNT_COMMISSION_VALUE']) ? $arBank['~PROPERTY_PERSONAL_ACCOUNT_COMMISSION_VALUE'] . (!empty($arBank['~PROPERTY_PERSONAL_ACCOUNT_COMMISSION_DESCRIPTION']) ? '<br><small>' . $arBank['~PROPERTY_PERSONAL_ACCOUNT_COMMISSION_DESCRIPTION'] . '</small>' : '') : '—' ?></td>
                                                        </tr>
                                                    <? } ?>
                                                    </tbody>
                                                </table>
                                                <?
                                                $tableHTML = ob_get_contents();
                                                ob_end_clean();

                                                break;
                                            case 39:
                                                $arBanks = [];
                                                $arSelect = [
                                                    'ID',
                                                    'NAME',
                                                    'PROPERTY_LOGO',
                                                    'PROPERTY_PRICE_SBR',
                                                ];
                                                $arFilter = ['IBLOCK_ID' => Config::BankIBlock, 'ACTIVE' => 'Y', '!PROPERTY_PRICE_SBR' => false];
                                                $res = \CIBlockElement::GetList(['SORT' => 'ASC'], $arFilter, false, false, $arSelect);
                                                while ($ob = $res->GetNextElement()) {
                                                    $arFields = $ob->GetFields();
                                                    $arFields['NAME'] = $arFields['~NAME'];
                                                    if (!empty($arFields['PROPERTY_LOGO_VALUE'])) {
                                                        $arFields['LOGO'] = Helper::GetFilePath($arFields['PROPERTY_LOGO_VALUE']);
                                                    }

                                                    $arBanks[] = $arFields;
                                                }

                                                if (empty($arBanks)) {
                                                    break;
                                                }

                                                ob_start();
                                                ?>
                                                <table>
                                                    <thead>
                                                    <tr>
                                                        <td>Банк</td>
                                                        <td>Стоимость оформления</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <? foreach ($arBanks as $arBank) { ?>
                                                        <tr>
                                                            <td><? if (!empty($arBank['LOGO'])) { ?><img src="<?= $arBank['LOGO'] ?>" alt="<?= $arBank['NAME'] ?>"><? } ?><?= $arBank['NAME'] ?></td>
                                                            <td>
                                                                <? foreach ($arBank['PROPERTY_PRICE_SBR_VALUE'] as $i => $arItem) { ?>
                                                                    <div><?= !empty($arItem) ? (!empty($arBank['~PROPERTY_PRICE_SBR_DESCRIPTION'][$i]) ? '<small>' . $arBank['~PROPERTY_PRICE_SBR_DESCRIPTION'][$i] . '</small><br>' : '') . $arItem : '—' ?></div>
                                                                <? } ?>
                                                            </td>
                                                        </tr>
                                                    <? } ?>
                                                    </tbody>
                                                </table>
                                                <?
                                                $tableHTML = ob_get_contents();
                                                ob_end_clean();

                                                break;
                                        }
                                    }

                                    $tablesHTML .= $tableHTML;
                                }

                                if (!empty($tablesHTML)) {
                                    $arBlocks[(int)$arBlock['layout']]['blocks'][] = [
                                        'type' => 'table',
                                        'value' => $tablesHTML,
                                    ];
                                }
                            }
                        }
                    }
                    break;
            }
        }

        foreach ($arContent['layouts'] as $i => $layout) {
            if (!empty($arBlocks[$i])) {
                if (!empty($layout['columns'][0]['css'])) {
                    $arBlocks[$i]['style'] = $layout['columns'][0]['css'];
                } else {
                    $arBlocks[$i]['style'] = 'default';
                }
            }
        }

        return $arBlocks;
    }
}
