<?php
/* Правильная структура модуля:
local.authprovider/
├── install/
│ ├── index.php         ← главный файл установки
│ ├── step.php          ← сообщение после установки
│ ├── unstep.php        ← сообщение после удаления
│ └── version.php       ← версия модуля
├── include.php         ← автозагрузка классов
├── lib/
│ ├── AuthSimple.php
│ ├── EventProvider.php
│ └── AuthProvider.php
└── README.md           ← описание (опционально)
*/

// Автозагрузка классов модуля
Bitrix\Main\Loader::registerAutoLoadClasses(
    "local.authprovider",
    array(
        "\\Local\\AuthProvider\\AuthSimple" => "lib/AuthSimple.php",
        "\\Local\\AuthProvider\\EventProvider" => "lib/EventProvider.php",
        "\\Local\\AuthProvider\\AuthProvider" => "lib/AuthProvider.php",
    )
);