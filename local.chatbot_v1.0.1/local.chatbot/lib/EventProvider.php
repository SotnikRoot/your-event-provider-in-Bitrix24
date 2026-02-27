<?php
// Это **самый важный файл** — именно он решает вашу проблему, отправляя события напрямую на ваш Python-сервер вместо внешних серверов Битрикс:
namespace Local\AuthProvider;

use Bitrix\Rest\Event\ProviderInterface;
use Bitrix\Rest\Event\ProviderOAuth;
use Bitrix\Rest\Event\Sender;

class EventProvider extends ProviderOAuth implements ProviderInterface
{
    /**
     * Регистрируем себя как провайдер событий
     */
    public static function onEventManagerInitialize()
    {
        Sender::setProvider(static::instance());
    }

    /**
     * Переопределяем отправку событий.
     * Вместо отправки на внешние сервера Битрикс (curator.pro и т.д.)
     * делаем прямой HTTP POST запрос на URL обработчика бота.
     */
    public function send(array $queryData)
    {
        $http = new \Bitrix\Main\Web\HttpClient(array(
            'socketTimeout' => 5,
            'streamTimeout' => 10,
            'redirect'     => true,
            'redirectMax' => 3,
        ));

        $remainingData = array();

        foreach ($queryData as $key => $item)
        {
            // Проверяем, есть ли URL для отправки
            if (!empty($item['query']['QUERY_URL']))
            {
                $url = $item['query']['QUERY_URL'];

                // Если URL ведёт на наш локальный сервер — отправляем напрямую
                if ($this->isLocalUrl($url))
                {
                    // Логируем для отладки
                    $this->log("Sending event directly to: " . $url);

                    $postData = $item['query']['QUERY_DATA'];
                    $result = $http->post($url, $postData);
                    $this->log("Response code: " . $http->getStatus() . ", body: " . substr($result, 0, 500));
                }
                else
                {
                    // Не наш URL — собираем для отправки через стандартный механизм
                    $remainingData[] = $item;
                }
            }
            else
            {
                $remainingData[] = $item;
            }
        }

        // Если остались события для внешних обработчиков — пробуем отправить стандартно
        if (count($remainingData) > 0)
        {
            try
            {
                parent::send(array_values($remainingData));
            }
            catch (\Exception $e)
            {
                $this->log("Parent send failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Проверяет, является ли URL локальным (в вашей сети).
     * НАСТРОЙТЕ ПОД СЕБЯ!
     */
    protected function isLocalUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (empty($host))
        {
            return false;
        }

        // Добавьте сюда ваши локальные адреса/домены
        $localPatterns = array(
            '127.0.0.1',
            'localhost',
            // И вообще любые адреса с которыми мы хотим дружить
        );

        // Проверяем по IP-диапазонам
        foreach ($localPatterns as $pattern)
        {
            if (strpos($host, $pattern) === 0 || $host === $pattern)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Простое логирование для отладки.
     * Логи будут в /local/modules/local.chatbot/debug.log
     */
    protected function log($message)
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/local.chatbot/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}