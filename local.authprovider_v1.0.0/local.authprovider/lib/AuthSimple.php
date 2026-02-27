<?php
namespace Local\AuthProvider;

class AuthSimple
{
    const AUTH_TYPE = 'local_simple';
    const AUTH_PARAM_NAME = 'secret_word';

    // ВАЖНО: замените на свой надёжный пароль!
    const AUTH_PARAM_VALUE = 'НАПИШИ СЮДА СВОЁ СЕКРЕТНОЕ СЛОВО';

    public static function onRestCheckAuth(array $query, $scope, &$res)
    {
        // Если в запросе нет нашего параметра — это не наш запрос, пропускаем
        if (!array_key_exists(static::AUTH_PARAM_NAME, $query))
        {
            self::log("Not secret_word in AUTH_PARAM_NAME");
            return null;
        } else {
            self::log("Find secret_word in AUTH_PARAM_NAME");
        }

        // Если параметр есть, проверяем значение
        if ($query[static::AUTH_PARAM_NAME] === static::AUTH_PARAM_VALUE)
        {
            self::log("secret_word is true");
            $error = false;
            $res = array(
                'user_id' => 1, // ID пользователя, от имени которого работает бот
                'scope' => implode(',', \CRestUtil::getScopeList()),
                'parameters_clear' => array(static::AUTH_PARAM_NAME),
                'auth_type' => static::AUTH_TYPE,
            );

            if (!\CRestUtil::makeAuth($res))
            {
                $res = array(
                    'error' => 'authorization_error',
                    'error_description' => 'Unable to authorize user'
                );
                $error = true;
            }
            return !$error;
        }

        // Параметр есть, но значение неверное
        $res = array(
            'error' => 'INVALID_CREDENTIALS',
            'error_description' => 'Invalid request credentials'
        );
        return false;
    }

    public static function log($message)
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/local.authprovider/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}