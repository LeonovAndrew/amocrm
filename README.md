# MWI: Интеграция с AmoCRM для Битрикс

Модуль предоставляет удобный клиент для работы с API AmoCRM из среды Битрикс24 (D7).

## Установка

1.  Скопируйте папку `mwi.amocrm` в директорию `/local/modules/` вашего проекта Битрикс.
2.  В административной панели перейдите в раздел **Маркетплейс > Установленные решения**.
3.  Найдите модуль "Mwi: AmoCRM" и нажмите «Установить».
4.  Или установите через консоль:
    ```bash
    php /path/to/bitrix/bitrix.php module:install mwi.amocrm
    ```

## Настройка

Перед использованием необходимо задать учетные данные вашего AmoCRM в настройках модуля.

1.  Перейдите в раздел **Настройки > amoCRM> ДНастройки > Mwi: AmoCRM**.
2.  Заполните обязательные поля:
    *   **Base Domain**: Поддомен вашего аккаунта AmoCRM (например, `yourcompany` для `yourcompany.amocrm.ru`).
    *   **Client ID**: ID интеграции (из настроек AmoCRM).
    *   **Client Secret**: Секретный ключ интеграции (из настроек AmoCRM).
    *   **Authorization Code**: Код авторизации (генерируется в AmoCRM при создании интеграции).
    *   **Redirect URI**: URI перенаправления, указанный в настройках интеграции (например, `https://your-bitrix-site.ru/`).

## Использование

После установки и настройки модуль готов к работе. Подключите модуль и создайте экземпляр клиента.

### Базовый пример

```php
<?php

use Bitrix\Main\Loader;
use Mwi\Amocrm\Client;

// Подключаем модуль
Loader::includeModule("mwi.amocrm");

// Создаем экземпляр клиента
$client = new Client();

// 1. Создаем контакт
$contactData = [
    [
        'name' => 'Проверка',
        'custom_fields_values' => [
            [
                'field_code' => 'PHONE',
                'values' => [
                    ['value' => '+71111111111', 'enum_code' => 'WORK']
                ]
            ],
            [
                'field_code' => 'EMAIL',
                'values' => [
                    ['value' => 'test@tes.ru', 'enum_code' => 'WORK']
                ]
            ]
        ]
    ]
];
$contactResp = $client->createContact($contactData);

// Проверяем успешность создания контакта и получаем его ID
if ($contactResp && isset($contactResp['body']['_embedded']['contacts'][0]['id'])) {
    $contactId = $contactResp['body']['_embedded']['contacts'][0]['id'];

    // 2. Создаем лид и привязываем к нему созданный контакт
    $leadData = [
        [
            'name' => "Название заявки",
            'price' => 0,
            'pipeline_id' => 0000000, // Обязательно замените на ID вашего воронки
            'custom_fields_values' => [
                [
                    'field_id' => 0000000, // Обязательно замените на ID вашего поля
                    'values' => [
                        ['value' => "Текст"]
                    ]
                ]
            ],
            '_embedded' => [
                'contacts' => [
                    ['id' => $contactId] // Привязываем контакт
                ]
            ]
        ]
    ];

    $leadResp = $client->createLead($leadData);

    // Обрабатываем ответ от создания лида
    if ($leadResp && isset($leadResp['body']['_embedded']['leads'][0]['id'])) {
        $leadId = $leadResp['body']['_embedded']['leads'][0]['id'];
        echo "Лид успешно создан, ID: " . $leadId;
    } else {
        // Обработка ошибок создания лида
        echo "Ошибка при создании лида: ";
        var_dump($leadResp);
    }
} else {
    // Обработка ошибок создания контакта
    echo "Ошибка при создании контакта: ";
    var_dump($contactResp);
}
?>
