<?php

namespace ApiBundle\Security;

use CoreBundle\Entity\User as UserEntity;
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

        $companyId = (int) $request->headers->get('CompanyId');
        if (!$companyId) {
            throw new \Exception('Required header CompanyId is missing.');
        }

        /** @var UserEntity $user */
        $user = $token->getUser();
        if (!$user->isEnabled()) {
            throw new \Exception('User is disabled.');
        }

        if ($user->getCompanies()->isEmpty()) {
            $token->getUser()->setActiveCompanyId($companyId);

            return null;
        }

        /** @var UserCompanyEntity $userCompany */
        foreach ($user->getCompanies() as $userCompany) {
            if ($userCompany->getOriginal()->getId() === $companyId) {
                $token->getUser()->setActiveCompanyId($companyId);

                return null;
            }
        }

        throw new \Exception('Access denied.');
    }
}
