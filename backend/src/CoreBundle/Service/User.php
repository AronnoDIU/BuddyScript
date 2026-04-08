<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\User as UserEntity;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Util\Pagination\PaginationFactory;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class User extends BaseService
{
    private readonly UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
    ) {
        $this->userPasswordHasher = $userPasswordHasher;
        parent::__construct($em, $tokenStorage);
    }

    public function setPassword(UserEntity $user): void
    {
        if (empty($user->getPlainPassword())) {
            return;
        }

        $user->setPassword($this->getHashedPassword($user, $user->getPlainPassword()));
    }

    public function getFormattedPhone(string $phone, ?string $region = null): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsedPhone = $phoneUtil->parse($phone, $region ?: 'BD');
        } catch (NumberParseException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        if (!$phoneUtil->isValidNumber($parsedPhone)) {
            throw new \RuntimeException(\sprintf('Phone no is not valid, %s', $phone));
        }

        return \sprintf('+%s%s', $parsedPhone->getCountryCode(), $parsedPhone->getNationalNumber());
    }

    public function getList(Request $request): array
    {
        $qb = $this->getUserRepository()
            ->filter($request);

        $qb = $this->getUserRepository()
            ->sort($request, $qb);

        return new PaginationFactory()
            ->createCollection($qb, $request)
            ->getData();
    }

    public function copyRoles(Request $request): void
    {
        if (!$fromUserId = $request->request->get('from_user')) {
            throw new \RuntimeException('From user is required.');
        }

        if (!$toUserId = $request->request->get('to_user')) {
            throw new \RuntimeException('To user is required.');
        }

        if (!$fromUser = $this->em->getRepository(UserEntity::class)->find($fromUserId)) {
            throw new \RuntimeException(\sprintf('From user id: %d not found.', $fromUserId));
        }

        if (!$toUser = $this->em->getRepository(UserEntity::class)->find($toUserId)) {
            throw new \RuntimeException(\sprintf('To user id: %d not found.', $toUserId));
        }

        $toUser->setRoles($fromUser->getRoles());
        $this->em->flush();
    }

    public function changeStatus(Request $request): void
    {
        /** @var UserEntity $user */
        $user = $this->getEntityObject(UserEntity::class, $request->get('id'));
        $enabled = $request->get('enabled');
        $user->setEnabled($enabled);
        $this->em->flush();
    }

    private function getUserRepository(): UserRepository
    {
        $repository = $this->em->getRepository(UserEntity::class);

        if (!$repository instanceof UserRepository) {
            throw new \LogicException('User repository is not configured correctly.');
        }

        return $repository;
    }

    private function getHashedPassword(UserEntity $user, string $password): string
    {
        return $this->userPasswordHasher->hashPassword($user, $password);
    }
}
