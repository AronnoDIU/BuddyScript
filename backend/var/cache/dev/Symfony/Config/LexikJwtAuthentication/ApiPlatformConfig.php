<?php

namespace Symfony\Config\LexikJwtAuthentication;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class ApiPlatformConfig 
{
    private $enabled;
    private $checkPath;
    private $usernamePath;
    private $passwordPath;
    private $_usedProperties = [];

    /**
     * @default false
     * @param ParamConfigurator|bool $value
     * @return $this
     */
    public function enabled($value): static
    {
        $this->_usedProperties['enabled'] = true;
        $this->enabled = $value;

        return $this;
    }

    /**
     * The login check path to add in OpenAPI.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function checkPath($value): static
    {
        $this->_usedProperties['checkPath'] = true;
        $this->checkPath = $value;

        return $this;
    }

    /**
     * The path to the username in the JSON body.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function usernamePath($value): static
    {
        $this->_usedProperties['usernamePath'] = true;
        $this->usernamePath = $value;

        return $this;
    }

    /**
     * The path to the password in the JSON body.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function passwordPath($value): static
    {
        $this->_usedProperties['passwordPath'] = true;
        $this->passwordPath = $value;

        return $this;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('enabled', $config)) {
            $this->_usedProperties['enabled'] = true;
            $this->enabled = $config['enabled'];
            unset($config['enabled']);
        }

        if (array_key_exists('check_path', $config)) {
            $this->_usedProperties['checkPath'] = true;
            $this->checkPath = $config['check_path'];
            unset($config['check_path']);
        }

        if (array_key_exists('username_path', $config)) {
            $this->_usedProperties['usernamePath'] = true;
            $this->usernamePath = $config['username_path'];
            unset($config['username_path']);
        }

        if (array_key_exists('password_path', $config)) {
            $this->_usedProperties['passwordPath'] = true;
            $this->passwordPath = $config['password_path'];
            unset($config['password_path']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['enabled'])) {
            $output['enabled'] = $this->enabled;
        }
        if (isset($this->_usedProperties['checkPath'])) {
            $output['check_path'] = $this->checkPath;
        }
        if (isset($this->_usedProperties['usernamePath'])) {
            $output['username_path'] = $this->usernamePath;
        }
        if (isset($this->_usedProperties['passwordPath'])) {
            $output['password_path'] = $this->passwordPath;
        }

        return $output;
    }

}
