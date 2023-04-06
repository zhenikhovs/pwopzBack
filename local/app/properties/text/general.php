<?php

class UserTextEditor
{
    // инициализация пользовательского свойства для главного модуля
    function GetUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID' => 'text',
            'CLASS_NAME' => 'UserTextEditor',
            'DESCRIPTION' => 'Текстовый редактор',
            'BASE_TYPE' => 'string',
        ];
    }

    // инициализация пользовательского свойства для инфоблока
    static function GetIBlockPropertyDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'text',
            'DESCRIPTION' => 'Текстовый редактор',
            'GetPropertyFieldHtml' => array('UserTextEditor', 'GetPropertyFieldHtml'),
            'GetAdminListViewHTML' => array('UserTextEditor', 'GetAdminListViewHTML'),
        ];
    }

    // представление свойства
    function GetViewHTML($value): string
    {
        return '<div style="display: block; width: 16px; height: 16px; background-color: #' . str_pad(dechex($value), 6, '0', STR_PAD_LEFT) . ';">&nbsp;</div>';
    }

    // редактирование свойства
    function GetEditHTML($arProperty, $arValue, $strHTMLControlName): string
    {
        CUtil::InitJSCore(['jquery']);

        ob_start(); ?>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.19.1/trumbowyg.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.19.1/ui/trumbowyg.min.css">
        <link rel="stylesheet" type="text/css" href="/local/app/properties/text/style.css">
        <div class="text-editor">
            <? if ($arProperty['WITH_DESCRIPTION'] == 'Y') { ?>
                <input style="margin-bottom: 10px" type="text" size="30" name="<?= $strHTMLControlName['DESCRIPTION'] ?>" placeholder="Заголовок" value="<?= $arValue['DESCRIPTION'] ?>">
            <? } ?>
            <textarea name="<?= $strHTMLControlName['VALUE'] ?>" class="trumbowyg-text-editor" placeholder="Текст"><?= $arValue['VALUE'] ?></textarea>
        </div>
        <script type="text/javascript" src="/local/app/properties/text/script.js"></script>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    // редактирование свойства в форме и списке (инфоблок)
    function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName): string
    {
        return $strHTMLControlName['MODE'] == 'FORM_FILL'
            ? self::GetEditHTML($arProperty, $arValue, $strHTMLControlName)
            : self::GetViewHTML($arValue['VALUE']);
    }
}

// добавляем тип для инфоблока
AddEventHandler('iblock', 'OnIBlockPropertyBuildList', array('UserTextEditor', 'GetIBlockPropertyDescription'));