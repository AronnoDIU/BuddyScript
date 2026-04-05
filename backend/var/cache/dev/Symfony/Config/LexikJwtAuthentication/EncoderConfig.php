<?php

namespace Symfony\Config\LexikJwtAuthentication;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class EncoderConfig 
{
    private $service;
    private $signatureAlgorithm;
    private $_usedProperties = [];

    /**
     * @default 'lexik_jwt_authentication.encoder.lcobucci'
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function service($value): static
    {
        $this->_usedProperties['service'] = true;
        $this->service = $value;

        return $this;
    }

    /**
     * @default 'RS256'
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function signatureAlgorithm($value): static
    {
        $this->_usedProperties['signatureAlgorithm'] = true;
        $this->signatureAlgorithm = $value;

        return $this;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('service', $config)) {
            $this->_usedProperties['service'] = true;
            $this->service = $config['service'];
            unset($config['service']);
        }

        if (array_key_exists('signature_algorithm', $config)) {
            $this->_usedProperties['signatureAlgorithm'] = true;
            $this->signatureAlgorithm = $config['signature_algorithm'];
            unset($config['signature_algorithm']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['service'])) {
            $output['service'] = $this->service;
        }
        if (isset($this->_usedProperties['signatureAlgorithm'])) {
            $output['signature_algorithm'] = $this->signatureAlgorithm;
        }

        return $output;
    }

}
