<?php

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/local.sorts/admin/listSorts.php")) {
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/local.sorts/admin/listSorts.php");
} else {
    require($_SERVER["DOCUMENT_ROOT"]."/local/modules/local.sorts/admin/listSorts.php");
}