<?php

namespace CoreBundle\Service;

use CoreBundle\Entity\User as UserEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseService
{
    protected TokenStorageInterface $tokenStorage;

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
    }

    public function getLoggedUser(): UserInterface|UserEntity|null
    {
        if ($this->tokenStorage->getToken()) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function save($object): bool
    {
        $this->em->persist($object);
        $this->em->flush();

        return true;
    }

    public function tryToDelete($object): bool
    {
        try {
            $this->em->remove($object);
            $this->em->flush();
        } catch (\Exception) {
            throw new \RuntimeException("We can't delete the object.
            Please make sure the object is not used anywhere before deleting.");
        }

        return true;
    }

    protected function convertTimeZoneToUtc(\DateTime $date): \DateTime
    {
        return (clone $date)->setTime('00', '00', '01')
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    protected function getEntityObject(string $className, $obj = null)
    {
        if (!$obj) {
            return null;
        }

        if ($obj instanceof $className) {
            return $obj;
        }

        if (!$obj = $this->em->getRepository($className)->find($obj)) {
            $shortClassName = explode('\\', $className);
            throw new \RuntimeException(\sprintf('%s not found, id: %d.', end($shortClassName), $obj));
        }

        return $this->validateCompanyAccess($this->em->getRepository($className)->find($obj));
    }

    protected function getDateTimeObject(?string $date): ?\DateTime
    {
        if (!$date) {
            return null;
        }

        try {
            return new \DateTime($date);
        } catch (\DateMalformedStringException $e) {
            throw new \RuntimeException(\sprintf('Invalid date format: %s', $date));
        }
    }

    protected function formatPhoneNo(string $mobile, string $region, bool $exception): ?string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $mobile = $phoneUtil->parse($mobile, $region ?: 'BD');
        } catch (NumberParseException $e) {
            if ($exception === false) {
                return null;
            }

            throw new \RuntimeException($e->getMessage());
        }

        if (!$phoneUtil->isValidNumber($mobile)) {
            if ($exception === false) {
                return null;
            }

            throw new \RuntimeException(\sprintf('Mobile no is not valid: %s', $mobile));
        }

        return \sprintf('+%s%s', $mobile->getCountryCode(), $mobile->getNationalNumber());
    }

    protected function validateDateRangeLimit(\DateTime $start, \DateTime $end, int $limit): void
    {
        $diff = $start->diff($end);
        if (($diff->days + 1) > $limit) {
            throw new \RuntimeException(\sprintf('The range of dates must be between %d days.', $limit));
        }
    }
}
