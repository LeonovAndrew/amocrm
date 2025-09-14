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
        //COption::SetOptionString("mwi.amocrm","access_token", $_POST['access_token']);
        //COption::SetOptionString("mwi.amocrm","refresh_token", $_POST['refresh_token']);
        //COption::SetOptionString("mwi.amocrm","token_expires", $_POST['token_expires']);
        CAdminMessage::ShowMessage(["MESSAGE"=>"Настройки сохранены","TYPE"=>"OK"]);
    }

    Loader::includeModule("mwi.amocrm");

    $clientId    = COption::GetOptionString("mwi.amocrm","client_id","");
    $clientSecret= COption::GetOptionString("mwi.amocrm","client_secret","");
    $redirect    = COption::GetOptionString("mwi.amocrm","redirect_uri","");
    $account     = COption::GetOptionString("mwi.amocrm","account_domain","");

    $accessToken  = COption::GetOptionString("mwi.amocrm","access_token","");
    $refreshToken = COption::GetOptionString("mwi.amocrm","refresh_token","");
    $tokenExpires = COption::GetOptionString("mwi.amocrm","token_expires","");

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
            <!--<tr>
                <td>access Token:</td>
                <td><input type="text" name="access_token" value="<?/*=$accessToken*/?>" size="50" disabled /></td>
            </tr>

            <tr>
                <td>refresh Token:</td>
                <td><input type="text" name="refresh_token" value="<?/*=$refreshToken*/?>" size="50" disabled /></td>
            </tr>-->
            <tr>
                <td>token Expires:</td>
                <td><input type="text" name="token_expires" value="<?=$tokenExpires?>" size="50" disabled /></td>
            </tr>
        </table>
        <br>
        <input type="submit" value="Сохранить" class="adm-btn-save" />
    </form>

    <br>
    <?php if(empty($tokenExpires)):?>
        <a class="adm-btn" href="<?=$authUrl?>" target="_blank">Подключиться к amoCRM</a>
    <?php endif;?>

    <?php if(false): //$accessToken || $refreshToken?>
    <h3>Текущие токены:</h3>
    <table class="adm-detail-content-table edit-table">
        <tr>
            <td width="40%">Access Token:</td>
            <td width="60%"><input type="text" value="<?=$accessToken?>" size="80" readonly /></td>
        </tr>
        <tr>
            <td>Refresh Token:</td>
            <td><input type="text" value="<?=$refreshToken?>" size="80" readonly /></td>
        </tr>
        <tr>
            <td>Token Expires:</td>
            <td><input type="text" value="<?=date('Y-m-d H:i:s', $tokenExpires)?>" size="50" readonly /></td>
        </tr>
    </table>
<?php endif;
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");