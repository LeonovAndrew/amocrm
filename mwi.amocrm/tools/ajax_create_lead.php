<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/mwi.amocrm/lib/amocrm_client.php");

header('Content-Type: application/json; charset=utf-8');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    die;
}

$lead = [
    'name' => $_POST['name'] ?? 'Lead from site',
    'price' => intval($_POST['price'] ?? 0),
];

$client = new MwiAmoClient();
$res = $client->createLead($lead);

echo json_encode($res);
