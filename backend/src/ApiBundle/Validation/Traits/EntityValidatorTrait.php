<?php

namespace ApiBundle\Validation\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Respect\Validation\Validator;

trait EntityValidatorTrait
{
    protected EntityManagerInterface $em;

    protected function entityExists(string $entity, string $identifier = 'id', array $criteria = [])
    {
        $repository = $this->em->getRepository($entity);

        return Validator::allOf(
            Validator::callback(static function ($input) use ($repository, $identifier, $criteria) {
                $criteria = array_merge([$identifier => $input], $criteria);

                return (bool) $repository->findOneBy($criteria);
            })
        );
    }
}
