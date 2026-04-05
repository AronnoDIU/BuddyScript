<?php

namespace Symfony\Config\LexikJwtAuthentication\AccessTokenIssuance;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class SignatureConfig 
{
    private $algorithm;
    private $key;
    private $_usedProperties = [];

    /**
     * The algorithm use to sign the access tokens.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function algorithm($value): static
    {
        $this->_usedProperties['algorithm'] = true;
        $this->algorithm = $value;

        return $this;
    }

    /**
     * The signature key. It shall be JWK encoded.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function key($value): static
    {
        $this->_usedProperties['key'] = true;
        $this->key = $value;

        return $this;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('algorithm', $config)) {
            $this->_usedProperties['algorithm'] = true;
            $this->algorithm = $config['algorithm'];
            unset($config['algorithm']);
        }

        if (array_key_exists('key', $config)) {
            $this->_usedProperties['key'] = true;
            $this->key = $config['key'];
            unset($config['key']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['algorithm'])) {
            $output['algorithm'] = $this->algorithm;
        }
        if (isset($this->_usedProperties['key'])) {
            $output['key'] = $this->key;
        }

        return $output;
    }

}
