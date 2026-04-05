<?php

namespace Symfony\Config\LexikJwtAuthentication;

require_once __DIR__.\DIRECTORY_SEPARATOR.'TokenExtractors'.\DIRECTORY_SEPARATOR.'AuthorizationHeaderConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'TokenExtractors'.\DIRECTORY_SEPARATOR.'CookieConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'TokenExtractors'.\DIRECTORY_SEPARATOR.'QueryParameterConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'TokenExtractors'.\DIRECTORY_SEPARATOR.'SplitCookieConfig.php';

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class TokenExtractorsConfig 
{
    private $authorizationHeader;
    private $cookie;
    private $queryParameter;
    private $splitCookie;
    private $_usedProperties = [];

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":true,"prefix":"Bearer","name":"Authorization"}
     * @return \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig : static)
     */
    public function authorizationHeader(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig|static
    {
        if (!\is_array($value)) {
            $this->_usedProperties['authorizationHeader'] = true;
            $this->authorizationHeader = $value;

            return $this;
        }

        if (!$this->authorizationHeader instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig) {
            $this->_usedProperties['authorizationHeader'] = true;
            $this->authorizationHeader = new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "authorizationHeader()" has already been initialized. You cannot pass values the second time you call authorizationHeader().');
        }

        return $this->authorizationHeader;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"name":"BEARER"}
     * @return \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig : static)
     */
    public function cookie(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig|static
    {
        if (!\is_array($value)) {
            $this->_usedProperties['cookie'] = true;
            $this->cookie = $value;

            return $this;
        }

        if (!$this->cookie instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig) {
            $this->_usedProperties['cookie'] = true;
            $this->cookie = new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "cookie()" has already been initialized. You cannot pass values the second time you call cookie().');
        }

        return $this->cookie;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"name":"bearer"}
     * @return \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig : static)
     */
    public function queryParameter(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig|static
    {
        if (!\is_array($value)) {
            $this->_usedProperties['queryParameter'] = true;
            $this->queryParameter = $value;

            return $this;
        }

        if (!$this->queryParameter instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig) {
            $this->_usedProperties['queryParameter'] = true;
            $this->queryParameter = new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "queryParameter()" has already been initialized. You cannot pass values the second time you call queryParameter().');
        }

        return $this->queryParameter;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"cookies":[]}
     * @return \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig : static)
     */
    public function splitCookie(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig|static
    {
        if (!\is_array($value)) {
            $this->_usedProperties['splitCookie'] = true;
            $this->splitCookie = $value;

            return $this;
        }

        if (!$this->splitCookie instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig) {
            $this->_usedProperties['splitCookie'] = true;
            $this->splitCookie = new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "splitCookie()" has already been initialized. You cannot pass values the second time you call splitCookie().');
        }

        return $this->splitCookie;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('authorization_header', $config)) {
            $this->_usedProperties['authorizationHeader'] = true;
            $this->authorizationHeader = \is_array($config['authorization_header']) ? new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig($config['authorization_header']) : $config['authorization_header'];
            unset($config['authorization_header']);
        }

        if (array_key_exists('cookie', $config)) {
            $this->_usedProperties['cookie'] = true;
            $this->cookie = \is_array($config['cookie']) ? new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig($config['cookie']) : $config['cookie'];
            unset($config['cookie']);
        }

        if (array_key_exists('query_parameter', $config)) {
            $this->_usedProperties['queryParameter'] = true;
            $this->queryParameter = \is_array($config['query_parameter']) ? new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig($config['query_parameter']) : $config['query_parameter'];
            unset($config['query_parameter']);
        }

        if (array_key_exists('split_cookie', $config)) {
            $this->_usedProperties['splitCookie'] = true;
            $this->splitCookie = \is_array($config['split_cookie']) ? new \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig($config['split_cookie']) : $config['split_cookie'];
            unset($config['split_cookie']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['authorizationHeader'])) {
            $output['authorization_header'] = $this->authorizationHeader instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\AuthorizationHeaderConfig ? $this->authorizationHeader->toArray() : $this->authorizationHeader;
        }
        if (isset($this->_usedProperties['cookie'])) {
            $output['cookie'] = $this->cookie instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\CookieConfig ? $this->cookie->toArray() : $this->cookie;
        }
        if (isset($this->_usedProperties['queryParameter'])) {
            $output['query_parameter'] = $this->queryParameter instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\QueryParameterConfig ? $this->queryParameter->toArray() : $this->queryParameter;
        }
        if (isset($this->_usedProperties['splitCookie'])) {
            $output['split_cookie'] = $this->splitCookie instanceof \Symfony\Config\LexikJwtAuthentication\TokenExtractors\SplitCookieConfig ? $this->splitCookie->toArray() : $this->splitCookie;
        }

        return $output;
    }

}
