<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight("local.sorts") > "D") {

    require_once(Loader::getLocal('modules/local.sorts/prolog.php'));

    // the types menu  dev.1c-bitrix.ru/api_help/main/general/admin.section/menu.php
    $aMenu = [
        "parent_menu" => "global_menu_content", // global_menu_content - раздел "Контент" global_menu_settings - раздел "Настройки"
        "section" => "local.sorts",
        "sort" => 880,
        "module_id" => "local.sorts",
        "text" => 'Сортировка элементов',
        "title"=> 'Сортирует модуль для дополнительного функционала',
        "icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "page_icon" => "fileman_menu_icon", // sys_menu_icon bizproc_menu_icon util_menu_icon
        "items_id" => "menu_local_sorts",
        "items" => [
            [
                "text" => 'Список элементов',
                "title" => 'Список элементов',
                "url" => "listSorts.php?mid=local.sorts&lang=".LANGUAGE_ID,
            ],
            [
                "text" => 'Настройки сортировки',
                "title" => 'Настройки сортировки',
                "url" => "settings.php?mid=local.sorts&lang=".LANGUAGE_ID,
            ],
        ]
    ];

    return $aMenu;
}

return false;