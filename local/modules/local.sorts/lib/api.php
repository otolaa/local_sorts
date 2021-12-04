<?php
namespace Local\Sorts;

use \Bitrix\Main\Config\Option;

/**
 * Class Api
 * @package Local\Sorts
 */
class Api
{
    static $MODULE_ID = "local.sorts";

    /* return array ID infoblock */
    public static function getIblockArr()
    {
        $arr = trim(Option::get(self::$MODULE_ID, "SORTS_IBLOCK_ARR", '1,2,3'));
        return array_filter(explode(',', $arr), 'strlen');
    }

}