<?php
use Bitrix\Main\Loader;
use Mwi\Amocrm\Client;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$APPLICATION->SetTitle("amoCRM: Авторизация");

Loader::includeModule("mwi.amocrm");

$code = $_GET['code'] ?? null;

if(!$code)
{
    CAdminMessage::ShowMessage([
        "TYPE" => "ERROR",
        "MESSAGE" => "Не передан параметр code в ответе от amoCRM"
    ]);
}
else
{
    $client = new Client();
    $res = $client->exchangeCodeForToken($code);

    if($res === true)
    {
        CAdminMessage::ShowMessage([
            "TYPE" => "OK",
            "MESSAGE" => "Успешно подключено. Токены сохранены."
        ]);
    }
    else
    {
        CAdminMessage::ShowMessage([
            "TYPE" => "ERROR",
            "MESSAGE" => "Ошибка при получении токенов",
            "DETAILS" => "<pre>".print_r($res, true)."</pre>",
            "HTML" => true
        ]);
    }
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
