<?php

namespace ApiBundle\Security;

use CoreBundle\Entity\User as UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class AuthenticationSuccessHandler
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private readonly JWTManager $jwtTokenManager;

    private readonly EventDispatcherInterface $dispatcher;

    private readonly AuthorizationCheckerInterface $authorizationChecker;

    private readonly EntityManagerInterface $em;

    private readonly SerializerInterface $jmsSerializer;

    public function __construct(
        JWTTokenManagerInterface $jwtTokenManager,
        EventDispatcherInterface $dispatcher,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em,
        SerializerInterface $jmsSerializer,
    ) {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->dispatcher = $dispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
        $this->jmsSerializer = $jmsSerializer;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {

    }
}
