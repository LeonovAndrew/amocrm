<?php
if ($APPLICATION->GetGroupRight("mwi.amocrm") != "D")
{
    $aMenu = [
        "parent_menu" => "global_menu_settings",
        "section"     => "mwi_amocrm",
        "sort"        => 100,
        "text"        => "amoCRM",
        "title"       => "Настройки интеграции amoCRM",
        "items_id"    => "menu_mwi_amocrm",
        "items"       => [
            [
                "text"  => "Настройки",
                "title" => "Настройки подключения amoCRM",
                "url"   => "mwi_amocrm_settings.php?lang=".LANG,
            ],
        ]
    ];
    return $aMenu;
}
return false;
