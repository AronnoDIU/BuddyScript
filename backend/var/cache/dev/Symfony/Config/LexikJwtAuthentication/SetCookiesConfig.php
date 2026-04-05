<?php

namespace Symfony\Config\LexikJwtAuthentication;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class SetCookiesConfig 
{
    private $lifetime;
    private $samesite;
    private $path;
    private $domain;
    private $secure;
    private $httpOnly;
    private $partitioned;
    private $split;
    private $_usedProperties = [];

    /**
     * The cookie lifetime. If null, the "token_ttl" option value will be used
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function lifetime($value): static
    {
        $this->_usedProperties['lifetime'] = true;
        $this->lifetime = $value;

        return $this;
    }

    /**
     * @default 'lax'
     * @param ParamConfigurator|'none'|'lax'|'strict' $value
     * @return $this
     */
    public function samesite($value): static
    {
        $this->_usedProperties['samesite'] = true;
        $this->samesite = $value;

        return $this;
    }

    /**
     * @default '/'
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function path($value): static
    {
        $this->_usedProperties['path'] = true;
        $this->path = $value;

        return $this;
    }

    /**
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function domain($value): static
    {
        $this->_usedProperties['domain'] = true;
        $this->domain = $value;

        return $this;
    }

    /**
     * @default true
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function secure($value): static
    {
        $this->_usedProperties['secure'] = true;
        $this->secure = $value;

        return $this;
    }

    /**
     * @default true
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function httpOnly($value): static
    {
        $this->_usedProperties['httpOnly'] = true;
        $this->httpOnly = $value;

        return $this;
    }

    /**
     * @default false
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function partitioned($value): static
    {
        $this->_usedProperties['partitioned'] = true;
        $this->partitioned = $value;

        return $this;
    }

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     */
    public function split(ParamConfigurator|array $value): static
    {
        $this->_usedProperties['split'] = true;
        $this->split = $value;

        return $this;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('lifetime', $config)) {
            $this->_usedProperties['lifetime'] = true;
            $this->lifetime = $config['lifetime'];
            unset($config['lifetime']);
        }

        if (array_key_exists('samesite', $config)) {
            $this->_usedProperties['samesite'] = true;
            $this->samesite = $config['samesite'];
            unset($config['samesite']);
        }

        if (array_key_exists('path', $config)) {
            $this->_usedProperties['path'] = true;
            $this->path = $config['path'];
            unset($config['path']);
        }

        if (array_key_exists('domain', $config)) {
            $this->_usedProperties['domain'] = true;
            $this->domain = $config['domain'];
            unset($config['domain']);
        }

        if (array_key_exists('secure', $config)) {
            $this->_usedProperties['secure'] = true;
            $this->secure = $config['secure'];
            unset($config['secure']);
        }

        if (array_key_exists('httpOnly', $config)) {
            $this->_usedProperties['httpOnly'] = true;
            $this->httpOnly = $config['httpOnly'];
            unset($config['httpOnly']);
        }

        if (array_key_exists('partitioned', $config)) {
            $this->_usedProperties['partitioned'] = true;
            $this->partitioned = $config['partitioned'];
            unset($config['partitioned']);
        }

        if (array_key_exists('split', $config)) {
            $this->_usedProperties['split'] = true;
            $this->split = $config['split'];
            unset($config['split']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['lifetime'])) {
            $output['lifetime'] = $this->lifetime;
        }
        if (isset($this->_usedProperties['samesite'])) {
            $output['samesite'] = $this->samesite;
        }
        if (isset($this->_usedProperties['path'])) {
            $output['path'] = $this->path;
        }
        if (isset($this->_usedProperties['domain'])) {
            $output['domain'] = $this->domain;
        }
        if (isset($this->_usedProperties['secure'])) {
            $output['secure'] = $this->secure;
        }
        if (isset($this->_usedProperties['httpOnly'])) {
            $output['httpOnly'] = $this->httpOnly;
        }
        if (isset($this->_usedProperties['partitioned'])) {
            $output['partitioned'] = $this->partitioned;
        }
        if (isset($this->_usedProperties['split'])) {
            $output['split'] = $this->split;
        }

        return $output;
    }

}
