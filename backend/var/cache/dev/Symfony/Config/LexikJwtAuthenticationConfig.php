<?php

namespace Symfony\Config;

require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'EncoderConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'TokenExtractorsConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'SetCookiesConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'ApiPlatformConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'AccessTokenIssuanceConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'AccessTokenVerificationConfig.php';
require_once __DIR__.\DIRECTORY_SEPARATOR.'LexikJwtAuthentication'.\DIRECTORY_SEPARATOR.'BlocklistTokenConfig.php';

use Symfony\Component\Config\Loader\ParamConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This class is automatically generated to help in creating a config.
 */
class LexikJwtAuthenticationConfig implements \Symfony\Component\Config\Builder\ConfigBuilderInterface
{
    private $publicKey;
    private $additionalPublicKeys;
    private $secretKey;
    private $passPhrase;
    private $tokenTtl;
    private $allowNoExpiration;
    private $clockSkew;
    private $encoder;
    private $userIdClaim;
    private $tokenExtractors;
    private $removeTokenFromBodyWhenCookiesUsed;
    private $setCookies;
    private $apiPlatform;
    private $accessTokenIssuance;
    private $accessTokenVerification;
    private $blocklistToken;
    private $_usedProperties = [];
    private $_hasDeprecatedCalls = false;

    /**
     * The key used to sign tokens (useless for HMAC). If not set, the key will be automatically computed from the secret key.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function publicKey($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['publicKey'] = true;
        $this->publicKey = $value;

        return $this;
    }

    /**
     * @param ParamConfigurator|list<ParamConfigurator|mixed> $value
     *
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function additionalPublicKeys(ParamConfigurator|array $value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['additionalPublicKeys'] = true;
        $this->additionalPublicKeys = $value;

        return $this;
    }

    /**
     * The key used to sign tokens. It can be a raw secret (for HMAC), a raw RSA/ECDSA key or the path to a file itself being plaintext or PEM.
     * @default null
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function secretKey($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['secretKey'] = true;
        $this->secretKey = $value;

        return $this;
    }

    /**
     * The key passphrase (useless for HMAC)
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function passPhrase($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['passPhrase'] = true;
        $this->passPhrase = $value;

        return $this;
    }

    /**
     * @default 3600
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function tokenTtl($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['tokenTtl'] = true;
        $this->tokenTtl = $value;

        return $this;
    }

    /**
     * Allow tokens without "exp" claim (i.e. indefinitely valid, no lifetime) to be considered valid. Caution: usage of this should be rare.
     * @default false
     * @param ParamConfigurator|bool $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function allowNoExpiration($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['allowNoExpiration'] = true;
        $this->allowNoExpiration = $value;

        return $this;
    }

    /**
     * @default 0
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function clockSkew($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['clockSkew'] = true;
        $this->clockSkew = $value;

        return $this;
    }

    /**
     * @default {"service":"lexik_jwt_authentication.encoder.lcobucci","signature_algorithm":"RS256"}
     * @deprecated since Symfony 7.4
     */
    public function encoder(array $value = []): \Symfony\Config\LexikJwtAuthentication\EncoderConfig
    {
        $this->_hasDeprecatedCalls = true;
        if (null === $this->encoder) {
            $this->_usedProperties['encoder'] = true;
            $this->encoder = new \Symfony\Config\LexikJwtAuthentication\EncoderConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "encoder()" has already been initialized. You cannot pass values the second time you call encoder().');
        }

        return $this->encoder;
    }

    /**
     * @default 'username'
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function userIdClaim($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['userIdClaim'] = true;
        $this->userIdClaim = $value;

        return $this;
    }

    /**
     * @default {"authorization_header":{"enabled":true,"prefix":"Bearer","name":"Authorization"},"cookie":{"enabled":false,"name":"BEARER"},"query_parameter":{"enabled":false,"name":"bearer"},"split_cookie":{"enabled":false,"cookies":[]}}
     * @deprecated since Symfony 7.4
     */
    public function tokenExtractors(array $value = []): \Symfony\Config\LexikJwtAuthentication\TokenExtractorsConfig
    {
        $this->_hasDeprecatedCalls = true;
        if (null === $this->tokenExtractors) {
            $this->_usedProperties['tokenExtractors'] = true;
            $this->tokenExtractors = new \Symfony\Config\LexikJwtAuthentication\TokenExtractorsConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "tokenExtractors()" has already been initialized. You cannot pass values the second time you call tokenExtractors().');
        }

        return $this->tokenExtractors;
    }

    /**
     * @default true
     * @param ParamConfigurator|mixed $value
     * @return $this
     * @deprecated since Symfony 7.4
     */
    public function removeTokenFromBodyWhenCookiesUsed($value): static
    {
        $this->_hasDeprecatedCalls = true;
        $this->_usedProperties['removeTokenFromBodyWhenCookiesUsed'] = true;
        $this->removeTokenFromBodyWhenCookiesUsed = $value;

        return $this;
    }

    /**
     * @deprecated since Symfony 7.4
     */
    public function setCookies(string $name, array $value = []): \Symfony\Config\LexikJwtAuthentication\SetCookiesConfig
    {
        $this->_hasDeprecatedCalls = true;
        if (!isset($this->setCookies[$name])) {
            $this->_usedProperties['setCookies'] = true;
            $this->setCookies[$name] = new \Symfony\Config\LexikJwtAuthentication\SetCookiesConfig($value);
        } elseif (1 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "setCookies()" has already been initialized. You cannot pass values the second time you call setCookies().');
        }

        return $this->setCookies[$name];
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * API Platform compatibility: add check_path in OpenAPI documentation.
     * @default {"enabled":false,"check_path":null,"username_path":null,"password_path":null}
     * @return \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig : static)
     * @deprecated since Symfony 7.4
     */
    public function apiPlatform(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig|static
    {
        $this->_hasDeprecatedCalls = true;
        if (!\is_array($value)) {
            $this->_usedProperties['apiPlatform'] = true;
            $this->apiPlatform = $value;

            return $this;
        }

        if (!$this->apiPlatform instanceof \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig) {
            $this->_usedProperties['apiPlatform'] = true;
            $this->apiPlatform = new \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "apiPlatform()" has already been initialized. You cannot pass values the second time you call apiPlatform().');
        }

        return $this->apiPlatform;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"signature":[],"encryption":{"enabled":false}}
     * @return \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig : static)
     * @deprecated since Symfony 7.4
     */
    public function accessTokenIssuance(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig|static
    {
        $this->_hasDeprecatedCalls = true;
        if (!\is_array($value)) {
            $this->_usedProperties['accessTokenIssuance'] = true;
            $this->accessTokenIssuance = $value;

            return $this;
        }

        if (!$this->accessTokenIssuance instanceof \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig) {
            $this->_usedProperties['accessTokenIssuance'] = true;
            $this->accessTokenIssuance = new \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "accessTokenIssuance()" has already been initialized. You cannot pass values the second time you call accessTokenIssuance().');
        }

        return $this->accessTokenIssuance;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"signature":{"header_checkers":[],"claim_checkers":["exp_with_clock_skew","iat_with_clock_skew","nbf_with_clock_skew"],"mandatory_claims":[],"allowed_algorithms":[]},"encryption":{"enabled":false,"continue_on_decryption_failure":false,"header_checkers":["iat_with_clock_skew","nbf_with_clock_skew","exp_with_clock_skew"],"allowed_key_encryption_algorithms":[],"allowed_content_encryption_algorithms":[]}}
     * @return \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig : static)
     * @deprecated since Symfony 7.4
     */
    public function accessTokenVerification(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig|static
    {
        $this->_hasDeprecatedCalls = true;
        if (!\is_array($value)) {
            $this->_usedProperties['accessTokenVerification'] = true;
            $this->accessTokenVerification = $value;

            return $this;
        }

        if (!$this->accessTokenVerification instanceof \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig) {
            $this->_usedProperties['accessTokenVerification'] = true;
            $this->accessTokenVerification = new \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "accessTokenVerification()" has already been initialized. You cannot pass values the second time you call accessTokenVerification().');
        }

        return $this->accessTokenVerification;
    }

    /**
     * @template TValue of array|bool
     * @param TValue $value
     * @default {"enabled":false,"cache":"cache.app"}
     * @return \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig|$this
     * @psalm-return (TValue is array ? \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig : static)
     * @deprecated since Symfony 7.4
     */
    public function blocklistToken(array|bool $value = []): \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig|static
    {
        $this->_hasDeprecatedCalls = true;
        if (!\is_array($value)) {
            $this->_usedProperties['blocklistToken'] = true;
            $this->blocklistToken = $value;

            return $this;
        }

        if (!$this->blocklistToken instanceof \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig) {
            $this->_usedProperties['blocklistToken'] = true;
            $this->blocklistToken = new \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig($value);
        } elseif (0 < \func_num_args()) {
            throw new InvalidConfigurationException('The node created by "blocklistToken()" has already been initialized. You cannot pass values the second time you call blocklistToken().');
        }

        return $this->blocklistToken;
    }

    public function getExtensionAlias(): string
    {
        return 'lexik_jwt_authentication';
    }

    public function __construct(array $config = [])
    {
        if (array_key_exists('public_key', $config)) {
            $this->_usedProperties['publicKey'] = true;
            $this->publicKey = $config['public_key'];
            unset($config['public_key']);
        }

        if (array_key_exists('additional_public_keys', $config)) {
            $this->_usedProperties['additionalPublicKeys'] = true;
            $this->additionalPublicKeys = $config['additional_public_keys'];
            unset($config['additional_public_keys']);
        }

        if (array_key_exists('secret_key', $config)) {
            $this->_usedProperties['secretKey'] = true;
            $this->secretKey = $config['secret_key'];
            unset($config['secret_key']);
        }

        if (array_key_exists('pass_phrase', $config)) {
            $this->_usedProperties['passPhrase'] = true;
            $this->passPhrase = $config['pass_phrase'];
            unset($config['pass_phrase']);
        }

        if (array_key_exists('token_ttl', $config)) {
            $this->_usedProperties['tokenTtl'] = true;
            $this->tokenTtl = $config['token_ttl'];
            unset($config['token_ttl']);
        }

        if (array_key_exists('allow_no_expiration', $config)) {
            $this->_usedProperties['allowNoExpiration'] = true;
            $this->allowNoExpiration = $config['allow_no_expiration'];
            unset($config['allow_no_expiration']);
        }

        if (array_key_exists('clock_skew', $config)) {
            $this->_usedProperties['clockSkew'] = true;
            $this->clockSkew = $config['clock_skew'];
            unset($config['clock_skew']);
        }

        if (array_key_exists('encoder', $config)) {
            $this->_usedProperties['encoder'] = true;
            $this->encoder = new \Symfony\Config\LexikJwtAuthentication\EncoderConfig($config['encoder']);
            unset($config['encoder']);
        }

        if (array_key_exists('user_id_claim', $config)) {
            $this->_usedProperties['userIdClaim'] = true;
            $this->userIdClaim = $config['user_id_claim'];
            unset($config['user_id_claim']);
        }

        if (array_key_exists('token_extractors', $config)) {
            $this->_usedProperties['tokenExtractors'] = true;
            $this->tokenExtractors = new \Symfony\Config\LexikJwtAuthentication\TokenExtractorsConfig($config['token_extractors']);
            unset($config['token_extractors']);
        }

        if (array_key_exists('remove_token_from_body_when_cookies_used', $config)) {
            $this->_usedProperties['removeTokenFromBodyWhenCookiesUsed'] = true;
            $this->removeTokenFromBodyWhenCookiesUsed = $config['remove_token_from_body_when_cookies_used'];
            unset($config['remove_token_from_body_when_cookies_used']);
        }

        if (array_key_exists('set_cookies', $config)) {
            $this->_usedProperties['setCookies'] = true;
            $this->setCookies = array_map(fn ($v) => new \Symfony\Config\LexikJwtAuthentication\SetCookiesConfig($v), $config['set_cookies']);
            unset($config['set_cookies']);
        }

        if (array_key_exists('api_platform', $config)) {
            $this->_usedProperties['apiPlatform'] = true;
            $this->apiPlatform = \is_array($config['api_platform']) ? new \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig($config['api_platform']) : $config['api_platform'];
            unset($config['api_platform']);
        }

        if (array_key_exists('access_token_issuance', $config)) {
            $this->_usedProperties['accessTokenIssuance'] = true;
            $this->accessTokenIssuance = \is_array($config['access_token_issuance']) ? new \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig($config['access_token_issuance']) : $config['access_token_issuance'];
            unset($config['access_token_issuance']);
        }

        if (array_key_exists('access_token_verification', $config)) {
            $this->_usedProperties['accessTokenVerification'] = true;
            $this->accessTokenVerification = \is_array($config['access_token_verification']) ? new \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig($config['access_token_verification']) : $config['access_token_verification'];
            unset($config['access_token_verification']);
        }

        if (array_key_exists('blocklist_token', $config)) {
            $this->_usedProperties['blocklistToken'] = true;
            $this->blocklistToken = \is_array($config['blocklist_token']) ? new \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig($config['blocklist_token']) : $config['blocklist_token'];
            unset($config['blocklist_token']);
        }

        if ($config) {
            throw new InvalidConfigurationException(sprintf('The following keys are not supported by "%s": ', __CLASS__).implode(', ', array_keys($config)));
        }
    }

    public function toArray(): array
    {
        $output = [];
        if (isset($this->_usedProperties['publicKey'])) {
            $output['public_key'] = $this->publicKey;
        }
        if (isset($this->_usedProperties['additionalPublicKeys'])) {
            $output['additional_public_keys'] = $this->additionalPublicKeys;
        }
        if (isset($this->_usedProperties['secretKey'])) {
            $output['secret_key'] = $this->secretKey;
        }
        if (isset($this->_usedProperties['passPhrase'])) {
            $output['pass_phrase'] = $this->passPhrase;
        }
        if (isset($this->_usedProperties['tokenTtl'])) {
            $output['token_ttl'] = $this->tokenTtl;
        }
        if (isset($this->_usedProperties['allowNoExpiration'])) {
            $output['allow_no_expiration'] = $this->allowNoExpiration;
        }
        if (isset($this->_usedProperties['clockSkew'])) {
            $output['clock_skew'] = $this->clockSkew;
        }
        if (isset($this->_usedProperties['encoder'])) {
            $output['encoder'] = $this->encoder->toArray();
        }
        if (isset($this->_usedProperties['userIdClaim'])) {
            $output['user_id_claim'] = $this->userIdClaim;
        }
        if (isset($this->_usedProperties['tokenExtractors'])) {
            $output['token_extractors'] = $this->tokenExtractors->toArray();
        }
        if (isset($this->_usedProperties['removeTokenFromBodyWhenCookiesUsed'])) {
            $output['remove_token_from_body_when_cookies_used'] = $this->removeTokenFromBodyWhenCookiesUsed;
        }
        if (isset($this->_usedProperties['setCookies'])) {
            $output['set_cookies'] = array_map(fn ($v) => $v->toArray(), $this->setCookies);
        }
        if (isset($this->_usedProperties['apiPlatform'])) {
            $output['api_platform'] = $this->apiPlatform instanceof \Symfony\Config\LexikJwtAuthentication\ApiPlatformConfig ? $this->apiPlatform->toArray() : $this->apiPlatform;
        }
        if (isset($this->_usedProperties['accessTokenIssuance'])) {
            $output['access_token_issuance'] = $this->accessTokenIssuance instanceof \Symfony\Config\LexikJwtAuthentication\AccessTokenIssuanceConfig ? $this->accessTokenIssuance->toArray() : $this->accessTokenIssuance;
        }
        if (isset($this->_usedProperties['accessTokenVerification'])) {
            $output['access_token_verification'] = $this->accessTokenVerification instanceof \Symfony\Config\LexikJwtAuthentication\AccessTokenVerificationConfig ? $this->accessTokenVerification->toArray() : $this->accessTokenVerification;
        }
        if (isset($this->_usedProperties['blocklistToken'])) {
            $output['blocklist_token'] = $this->blocklistToken instanceof \Symfony\Config\LexikJwtAuthentication\BlocklistTokenConfig ? $this->blocklistToken->toArray() : $this->blocklistToken;
        }
        if ($this->_hasDeprecatedCalls) {
            trigger_deprecation('symfony/config', '7.4', 'Calling any fluent method on "%s" is deprecated; pass the configuration to the constructor instead.', $this::class);
        }

        return $output;
    }

}
