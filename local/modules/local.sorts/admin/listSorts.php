<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/local.sorts/include.php"); // инициализация модуля
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/local.sorts/prolog.php"); // пролог модуля

// подключим языковой файл
IncludeModuleLangFile(__FILE__);
//
if(!CModule::IncludeModule("iblock")) return;
if(!CModule::IncludeModule("local.sorts")) return;

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("local.sorts");
// если нет прав - отправим к форме авторизации с сообщением об ошибке
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$sTableID = "tbl_notification_section"; // ID таблицы
$oSort = new CAdminSorting($sTableID, "id", "desc"); // объект сортировки
$lAdmin = new CAdminList($sTableID, $oSort); // основной объект списка

/* ФИЛЬТР */

// проверку значений фильтра для удобства вынесем в отдельную функцию
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;

    // В данном случае проверять нечего.
    // В общем случае нужно проверять значения переменных $find_имя
    // и в случае возниконовения ошибки передавать ее обработчику
    // посредством $lAdmin->AddFilterError('текст_ошибки').

    return count($lAdmin->arFilterErrors)==0; // если ошибки есть, вернем false;
}

// опишем элементы фильтра
$FilterArr = [
    "find",
    "find_type",
    "find_ID",
    "find_NAME",
];

// инициализируем фильтр
$lAdmin->InitFilter($FilterArr);

// если все значения фильтра корректны, обработаем его
if (CheckFilter())
{
    // создадим массив фильтрации для выборки
    $arFilter = [
        "ID"    => ($find!="" && $find_type == "id"? $find:$find_ID),
        "IBLOCK_ID"=>\Local\Sorts\Api::getIblockArr(),
        "NAME"=>($find_NAME?'%'.$find_NAME.'%':Null),
    ];
}

/*  КОНЕЦ ФИЛЬТРА */


/*  ОБРАБОТКА ДЕЙСТВИЙ НАД ЭЛЕМЕНТАМИ СПИСКА */

// сохранение отредактированных элементов
if($lAdmin->EditAction() && $POST_RIGHT=="W")
{
    // пройдем по списку переданных элементов
    foreach($FIELDS as $ID=>$arFields)
    {
        if (!$lAdmin->IsUpdated($ID)) continue;
        if (count($arFields)) {
            // сохраним изменения для каждого элемента
            $el = new CIBlockElement;
            $res = $el->Update($ID, $arFields);
        }
    }
}

// обработка одиночных и групповых действий
if(($arID = $lAdmin->GroupAction()) && $POST_RIGHT=="W")
{
    // если выбрано "Для всех элементов"
    if($_REQUEST['action_target']=='selected') {
        global $DB;
        $DB->StartTransaction();
        //$SQL = "DELETE FROM ``";
        //$dbResult = $DB->Query($SQL, true);
        $dbResult = false;
        $DB->Commit();
        if (!$dbResult) {
            $lAdmin->AddGroupError("Ошибка удаления");
        } else {
            CAdminMessage::ShowMessage(array("MESSAGE"=>"Удалены ", "TYPE"=>"OK"));
        }
    }

    // пройдем по списку элементов
    foreach($arID as $ID)
    {
        if(strlen($ID)<=0)continue;
        $ID = IntVal($ID);

        // для каждого элемента совершим требуемое действие
        switch($_REQUEST['action'])
        {
            // удаление
            case "delete":
                /**/
                global $DB;
                $DB->StartTransaction();
                if (!CIBlockElement::Delete($ID)) {
                    $DB->Rollback();
                    $lAdmin->AddGroupError("Ошибка удаления", $ID);
                } else {
                    $DB->Commit();
                    CAdminMessage::ShowMessage(array("MESSAGE"=>"Удалено ID ".$ID, "TYPE"=>"OK"));
                }
                break;

            // активация/деактивация
            case "activate":
            case "deactivate":
                $active = ($_REQUEST['action']=="activate"?"Y":"N");
                // обновляем и т.д.
                $lAdmin->AddGroupError("ОПЕРАЦИИ НАД ЭЛЕМЕНТАМИ НЕ ПРЕДУСМОТРЕННЫ", $ID);
                break;
            //  модерация
        }
    }
} // end GroupAction

/* Конец Обработки действия над элементами в таблитце */

/* ВЫБОРКА ЭЛЕМЕНТОВ СПИСКА */

// выберем список параметров
$arNavParams = ($_REQUEST["mode"] == "excel"?false:["nPageSize"=>CAdminResult::GetNavSize($sTableID)]);
$arSelect = ["ID", "NAME", "IBLOCK_ID", "IBLOCK_CODE", "SORT", "TIMESTAMP_X", "ACTIVE"];
$arFilter = array_filter($arFilter, function($v, $k) { return $v !== Null; }, ARRAY_FILTER_USE_BOTH);  // удаляем Null
$arSort = (count([$by=>$order])?[$by=>$order]:['SORT'=>'asc']);
$rsData = \CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);

$iTypeArr = []; // типы инфоблоков, нужны ниже
$resType = CIBlock::GetList([], ['ID'=>$arFilter['IBLOCK_ID']],false);
while($ar_res = $resType->Fetch()) {
    $iTypeArr[$ar_res['ID']] = $ar_res['IBLOCK_TYPE_ID'];
}

// преобразуем список в экземпляр класса CAdminResult
$rsData = new CAdminResult($rsData, $sTableID);

// аналогично CDBResult инициализируем постраничную навигацию.
$rsData->NavStart();

// отправим вывод переключателя страниц в основной объект $lAdmin
$lAdmin->NavText($rsData->GetNavPrint("Элементы"));

/* ПОДГОТОВКА СПИСКА К ВЫВОДУ */
$lAdmin->AddHeaders([
    array(  "id"    =>"ID",
        "content"  =>"ID",
        "sort"    =>"ID",
        //"align"    =>"left",
        "default"  =>true,
    ),
    array("id"   =>"NAME",
        "content"  =>"Заголовок",
        "sort"   =>"NAME",
        "default"  =>true,
    ),
    array("id"   =>"SORT",
        "content"  =>"Сортировка",
        "sort"   =>"SORT",
        "default"  =>true,
    ),
    array("id"   =>"IBLOCK_CODE",
        "content"  =>"ID / IBLOCK_CODE",
        "sort"   =>"IBLOCK_CODE",
        "default"  =>true,
    ),
    array("id"   =>"ACTIVE",
        "content"  =>"Активность",
        "sort"   =>"ACTIVE",
        "default"  =>true,
    ),
]);

// вывод и т.д.
while($arRes = $rsData->NavNext(true, "f_")):
    // создаем строку. результат - экземпляр класса CAdminListRow
    $row =& $lAdmin->AddRow($f_ID, $arRes);
    //
    $href_ ="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=".$f_IBLOCK_ID."&type=".$iTypeArr[$f_IBLOCK_ID]."&ID=".$f_ID."&lang=".LANGUAGE_ID."";
    $row->AddViewField("NAME",'<a href="'.$href_.'">'.$f_NAME.'</a>');

    $row->AddViewField("SORT",$f_SORT);
    $row->AddInputField("SORT", ["size"=>20]);

    $row->AddViewField("IBLOCK_CODE",$f_IBLOCK_ID." / ".$f_IBLOCK_CODE);

    $row->AddViewField("ACTIVE",($f_ACTIVE=='Y'?'Да':'Нет'));
    $row->AddCheckField("ACTIVE");

    /* контекстное меню */
    $arActions = [];
    // разделитель
    $arActions[] = ["SEPARATOR"=>true];
    // если последний элемент - разделитель, почистим мусор.
    if(is_set($arActions[count($arActions)-1], "SEPARATOR"))
        unset($arActions[count($arActions)-1]);

    // применим контекстное меню к строке
    $row->AddActions($arActions);

endwhile;


// резюме таблицы
$lAdmin->AddFooter(
    array(
        array("title"=>GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // кол-во элементов
        array("counter"=>true, "title"=>GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // счетчик выбранных элементов
    )
);

// групповые действия
$lAdmin->AddGroupActionTable(Array(
    //"delete"=>"Удалить", // удалить выбранные элементы
    //"activate"=>"Активировать", // активировать выбранные элементы
    //"deactivate"=>"Деактивировать", // деактивировать выбранные элементы
    // "modern"=>GetMessage("LIST_MODERN"), // отмодерировать выбранные элементы
));

/* ВЫВОД */
$lAdmin->CheckListMode();

// здесь будет вся серверная обработка и подготовка данных

$APPLICATION->SetTitle("Список элементов"); // H1
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

/* ВЫВОД ФИЛЬТРА */
// создадим объект фильтра
$oFilter = new CAdminFilter(
    $sTableID."_filter",
    array(
        "ID",
        "NAME",
        //GetMessage("find_code"),
        //GetMessage("find_name"),
    )
); ?>

<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
    <?$oFilter->Begin();?>
    <tr>
        <td><?="ID"?>:</td>
        <td>
            <input type="text" name="find_ID" size="47" value="<?=htmlspecialchars($find_ID)?>">
        </td>
    </tr>
    <tr>
        <td><?="NAME"?>:</td>
        <td>
            <input type="text" name="find_NAME" size="47" value="<?=htmlspecialchars($find_NAME)?>">
        </td>
    </tr>
    <?
    $oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
    $oFilter->End();
    ?>
</form>

<? // выведем таблицу списка элементов
$lAdmin->DisplayList();
// информационная подсказка
//echo BeginNote();
// echo "";
//echo EndNote(); ?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>
