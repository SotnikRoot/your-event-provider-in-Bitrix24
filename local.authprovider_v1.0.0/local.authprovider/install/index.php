<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

class local_authprovider extends CModule
{
    var $MODULE_ID = "local.authprovider";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = "Локальный провайдер авторизации и событий";
        $this->MODULE_DESCRIPTION = "Позволяет работать REST-приложениям и чат-ботам без подписки на Маркет, отправляя события напрямую на локальный сервер";
    }

    function DoInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        // Регистрируем модуль
        ModuleManager::registerModule($this->MODULE_ID);

        // Регистрируем обработчик для простой авторизации
        EventManager::getInstance()->registerEventHandler(
            "rest",
            "onRestCheckAuth",
            $this->MODULE_ID,
            "\\Local\\AuthProvider\\AuthSimple",
            "onRestCheckAuth",
            90
        );

        // Регистрируем инициализацию провайдера событий
        EventManager::getInstance()->registerEventHandler(
            "rest",
            "onEventManagerInitialize",
            $this->MODULE_ID,
            "\\Local\\AuthProvider\\EventProvider",
            "onEventManagerInitialize"
        );

        $APPLICATION->IncludeAdminFile(
            "Установка модуля " . $this->MODULE_ID,
            $DOCUMENT_ROOT . "/local/modules/" . $this->MODULE_ID . "/install/step.php"
        );
    }

    function DoUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;

        EventManager::getInstance()->unRegisterEventHandler(
            "rest",
            "onRestCheckAuth",
            $this->MODULE_ID,
            "\\Local\\AuthProvider\\AuthSimple",
            "onRestCheckAuth"
        );

        EventManager::getInstance()->unRegisterEventHandler(
            "rest",
            "onEventManagerInitialize",
            $this->MODULE_ID,
            "\\Local\\AuthProvider\\EventProvider",
            "onEventManagerInitialize"
        );

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            "Деинсталляция модуля " . $this->MODULE_ID,
            $DOCUMENT_ROOT . "/local/modules/" . $this->MODULE_ID . "/install/unstep.php"
        );
    }
}