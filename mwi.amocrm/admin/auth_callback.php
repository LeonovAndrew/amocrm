<?php
use Bitrix\Main\Loader;
use Mwi\Amocrm\Client;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

$APPLICATION->SetTitle("amoCRM: Авторизация");

Loader::includeModule("mwi.amocrm");

$code = $_GET['code'] ?? null;

$clientId = COption::GetOptionString("mwi.amocrm","client_id","");
$clientSecret = COption::GetOptionString("mwi.amocrm","client_secret","");
$redirectUri = COption::GetOptionString("mwi.amocrm","redirect_uri","");

// Проверяем все параметры
if (!$code || !$clientId || !$clientSecret || !$redirectUri) {
    CAdminMessage::ShowMessage([
        "TYPE" => "ERROR",
        "MESSAGE" => "Не все параметры заданы",
        "DETAILS" => "<pre>"
            . "code: " . var_export($code, true) . "\n"
            . "client_id: " . var_export($clientId, true) . "\n"
            . "client_secret: " . var_export($clientSecret, true) . "\n"
            . "redirect_uri: " . var_export($redirectUri, true)
            . "</pre>",
        "HTML" => true
    ]);
    require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
    exit;
}

$client = new Client();
$res = $client->exchangeCodeForToken($code);

// Логируем запрос и ответ
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/amocrm_debug.log', "code: {$code}\nresponse: ".print_r($res,true)."\n\n", FILE_APPEND);

if ($res === true) {
    CAdminMessage::ShowMessage([
        "TYPE" => "OK",
        "MESSAGE" => "Успешно подключено. Токены сохранены."
    ]);
} else {
    CAdminMessage::ShowMessage([
        "TYPE" => "ERROR",
        "MESSAGE" => "Ошибка при получении токенов",
        "DETAILS" => "<pre>".print_r($res, true)."</pre>",
        "HTML" => true
    ]);
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
