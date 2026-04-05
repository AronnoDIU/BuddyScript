<?php

namespace Symfony\Config\LexikJwtAuthentication\AccessTokenVerification;

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class SignatureConfig 
{
    private $headerCheckers;
    private $claimCheckers;
    private $mandatoryClaims;
    private $allowedAlgorithms;
    private $keyset;
    private $_usedProperties = [];

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     */
    public function headerCheckers(ParamConfigurator|array $value): static
    {
        $this->_usedProperties['headerCheckers'] = true;
        $this->headerCheckers = $value;

        return $this;
    }

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     */
    public function claimCheckers(ParamConfigurator|array $value): static
    {
        $this->_usedProperties['claimCheckers'] = true;
        $this->claimCheckers = $value;

        return $this;
    }

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     */
    public function mandatoryClaims(ParamConfigurator|array $value): static
    {
        $this->_usedProperties['mandatoryClaims'] = true;
        $this->mandatoryClaims = $value;

        return $this;
    }

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     */
    public function allowedAlgorithms(ParamConfigurator|array $value): static
    {
        $this->_usedProperties['allowedAlgorithms'] = true;
        $this->allowedAlgorithms = $value;

        return $this;
    }

    /**
     * The signature keyset. It shall be JWKSet encoded.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     */
    public function keyset($value): static
    {
        $this->_usedProperties['keyset'] = true;
        $this->keyset = $value;

        return $this;
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('header_checkers', $config)) {
            $this->_usedProperties['headerCheckers'] = true;
            $this->headerCheckers = $config['header_checkers'];
            unset($config['header_checkers']);
        }

        if (array_key_exists('claim_checkers', $config)) {
            $this->_usedProperties['claimCheckers'] = true;
            $this->claimCheckers = $config['claim_checkers'];
            unset($config['claim_checkers']);
        }

        if (array_key_exists('mandatory_claims', $config)) {
            $this->_usedProperties['mandatoryClaims'] = true;
            $this->mandatoryClaims = $config['mandatory_claims'];
            unset($config['mandatory_claims']);
        }

        if (array_key_exists('allowed_algorithms', $config)) {
            $this->_usedProperties['allowedAlgorithms'] = true;
            $this->allowedAlgorithms = $config['allowed_algorithms'];
            unset($config['allowed_algorithms']);
        }

        if (array_key_exists('keyset', $config)) {
            $this->_usedProperties['keyset'] = true;
            $this->keyset = $config['keyset'];
            unset($config['keyset']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['headerCheckers'])) {
            $output['header_checkers'] = $this->headerCheckers;
        }
        if (isset($this->_usedProperties['claimCheckers'])) {
            $output['claim_checkers'] = $this->claimCheckers;
        }
        if (isset($this->_usedProperties['mandatoryClaims'])) {
            $output['mandatory_claims'] = $this->mandatoryClaims;
        }
        if (isset($this->_usedProperties['allowedAlgorithms'])) {
            $output['allowed_algorithms'] = $this->allowedAlgorithms;
        }
        if (isset($this->_usedProperties['keyset'])) {
            $output['keyset'] = $this->keyset;
        }

        return $output;
    }

}
