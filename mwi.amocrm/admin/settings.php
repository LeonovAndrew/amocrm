<?php
use Bitrix\Main\Loader;
use Mwi\Amocrm\Client;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if ($USER->IsAdmin())
{
    if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid()){
        COption::SetOptionString("mwi.amocrm","client_id", $_POST['client_id']);
        COption::SetOptionString("mwi.amocrm","client_secret", $_POST['client_secret']);
        COption::SetOptionString("mwi.amocrm","redirect_uri", $_POST['redirect_uri']);
        COption::SetOptionString("mwi.amocrm","account_domain", $_POST['account_domain']);
        CAdminMessage::ShowMessage(["MESSAGE"=>"Настройки сохранены","TYPE"=>"OK"]);
    }

    Loader::includeModule("mwi.amocrm");

    $clientId    = COption::GetOptionString("mwi.amocrm","client_id","");
    $clientSecret= COption::GetOptionString("mwi.amocrm","client_secret","");
    $redirect    = COption::GetOptionString("mwi.amocrm","redirect_uri","");
    $account     = COption::GetOptionString("mwi.amocrm","account_domain","");

    $client = new Client();
    $authUrl = $client->getAuthUrl();
    ?>
    <form method="post">
        <?=bitrix_sessid_post()?>
        <table class="adm-detail-content-table edit-table">
            <tr>
                <td width="40%">Client ID:</td>
                <td width="60%"><input type="text" name="client_id" value="<?=$clientId?>" size="50" /></td>
            </tr>
            <tr>
                <td>Client Secret:</td>
                <td><input type="text" name="client_secret" value="<?=$clientSecret?>" size="50" /></td>
            </tr>
            <tr>
                <td>Redirect URI:</td>
                <td><input type="text" name="redirect_uri" value="<?=$redirect?>" size="50" /></td>
            </tr>
            <tr>
                <td>Account Domain:</td>
                <td><input type="text" name="account_domain" value="<?=$account?>" size="50" placeholder="example.amocrm.ru" /></td>
            </tr>
        </table>
        <br>
        <input type="submit" value="Сохранить" class="adm-btn-save" />
    </form>

    <br>
    <a class="adm-btn" href="<?=$authUrl?>" target="_blank">Подключиться к amoCRM</a>
    <?php
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
