<?php

namespace ApiBundle\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JwtTokenAuthenticator
 */
class JwtTokenAuthenticator extends JWTAuthenticator
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ('api' !== $firewallName) {
            return null;
        }

        // Keep JWT requests stateless: successful authentication should continue the request pipeline.
        return null;
    }
}
