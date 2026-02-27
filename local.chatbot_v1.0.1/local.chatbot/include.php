<?php
/* Правильная структура модуля:
local.chatbot/
├── install/
│ ├── index.php         ← главный файл установки
│ ├── step.php          ← сообщение после установки
│ ├── unstep.php        ← сообщение после удаления
│ └── version.php       ← версия модуля
├── include.php         ← автозагрузка классов
└── lib/
  └── EventProvider.php
*/

// Автозагрузка классов модуля
Bitrix\Main\Loader::registerAutoLoadClasses(
    "local.chatbot",
    array(
        "\\Local\\AuthProvider\\EventProvider" => "lib/EventProvider.php",
    )
);