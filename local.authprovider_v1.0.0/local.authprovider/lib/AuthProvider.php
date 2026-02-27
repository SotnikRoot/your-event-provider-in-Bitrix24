<?php
namespace Local\AuthProvider;

use Bitrix\Main\Context;
use Bitrix\Main\Security\Random;
use Bitrix\Rest\Application;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\AuthProviderInterface;
use Bitrix\Rest\OAuth\Provider;
use Bitrix\Rest\RestException;

class AuthProvider extends Provider implements AuthProviderInterface
{
    const TOKEN_TTL = 3600;
    const TOKEN_PREFIX = 'local.';

    protected $applicationList = array();
    protected static $instance = null;

    // Простое хранилище токенов в файле
    protected $storageFile;

    public static function instance()
    {
        if (static::$instance === null)
        {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct()
    {
        $this->storageFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/local.authprovider/tokens.json';
    }

    public static function onApplicationManagerInitialize()
    {
        Application::setAuthProvider(static::instance());
    }

    public function get($clientId, $scope, $additionalParams, $userId)
    {
        if (!$this->checkClient($clientId))
        {
            return parent::get($clientId, $scope, $additionalParams, $userId);
        }

        if ($userId > 0)
        {
            $applicationData = AppTable::getByClientId($clientId);
            if ($applicationData)
            {
                $authResult = array(
                    'access_token'    => $this->generateToken(),
                    'user_id'         => $userId,
                    'client_id'     => $clientId,
                    'expires'         => time() + static::TOKEN_TTL,
                    'expires_in'     => static::TOKEN_TTL,
                    'scope'         => $applicationData['SCOPE'],
                    'domain'         => Context::getCurrent()->getServer()->getHttpHost(),
                    'status'         => AppTable::STATUS_LOCAL,
                    'client_endpoint' => \CRestUtil::getEndpoint(),
                    'member_id'     => \CRestUtil::getMemberId(),
                );

                $this->store($authResult);
                return $authResult;
            }
            else
            {
                return array('error' => RestException::ERROR_OAUTH, 'error_description' => 'Application not installed');
            }
        }
        return false;
    }

    public function authorizeClient($clientId, $userId, $state = '')
    {
        if (!$this->checkClient($clientId))
        {
            return parent::authorizeClient($clientId, $userId, $state);
        }
        return false;
    }

    public function checkClient($clientId)
    {
        return in_array($clientId, $this->applicationList);
    }

    public function addApplication($clientId)
    {
        $this->applicationList[] = $clientId;
        return $this;
    }

    public function checkToken($token)
    {
        return substr($token, 0, strlen(static::TOKEN_PREFIX)) === static::TOKEN_PREFIX;
    }

    protected function generateToken()
    {
        return static::TOKEN_PREFIX . Random::getString(32);
    }

    protected function store(array $authResult)
    {
        $tokens = $this->loadTokens();
        $tokens[$authResult['access_token']] = $authResult;
        // Чистим просроченные
        $now = time();
        foreach ($tokens as $key => $val)
        {
            if (isset($val['expires']) && $val['expires'] < $now)
            {
                unset($tokens[$key]);
            }
        }
        file_put_contents($this->storageFile, json_encode($tokens));
    }

    public function restore($accessToken)
    {
        $tokens = $this->loadTokens();
        if (isset($tokens[$accessToken]))
        {
            if ($tokens[$accessToken]['expires'] > time())
            {
                return $tokens[$accessToken];
            }
        }
        return false;
    }

    protected function loadTokens()
    {
        if (file_exists($this->storageFile))
        {
            $data = json_decode(file_get_contents($this->storageFile), true);
            return is_array($data) ? $data : array();
        }
        return array();
    }
}