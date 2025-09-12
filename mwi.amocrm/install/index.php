<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class mwi_amocrm extends CModule
{
    public $MODULE_ID = "mwi.amocrm";
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $PARTNER_NAME = "MWI";
    public $PARTNER_URI = "https://example.com";

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = "Интеграция с amoCRM";
        $this->MODULE_DESCRIPTION = "Модуль для создания лидов в amoCRM из Bitrix.";
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        // псевдонимы админских файлов
        $adminDir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/";
        if(!file_exists($adminDir."mwi_amocrm_settings.php")){
            file_put_contents(
                $adminDir."mwi_amocrm_settings.php",
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."/local/modules/mwi.amocrm/admin/settings.php"); ?'.'>'
            );
        }
        if(!file_exists($adminDir."mwi_amocrm_auth.php")){
            file_put_contents(
                $adminDir."mwi_amocrm_auth.php",
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."/local/modules/mwi.amocrm/admin/auth_callback.php"); ?'.'>'
            );
        }
        if(!file_exists($adminDir."mwi_amocrm_menu.php")){
            file_put_contents(
                $adminDir."mwi_amocrm_menu.php",
                '<'.'? require($_SERVER["DOCUMENT_ROOT"]."/local/modules/mwi.amocrm/admin/menu.php"); ?'.'>'
            );
        }
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);

        // удаляем алиасы
        @unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/mwi_amocrm_settings.php");
        @unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/mwi_amocrm_auth.php");
        @unlink($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/mwi_amocrm_menu.php");
    }
}
