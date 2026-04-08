<?php

namespace ApiBundle\Validation\Traits;

use Respect\Validation\Validators\Satisfies;

trait EntityValidatorTrait
{

    protected function entityExists(string $entity, string $identifier = 'id', array $criteria = []): Satisfies
    {
        $repository = $this->em->getRepository($entity);

        return new Satisfies(static function ($input) use ($repository, $identifier, $criteria): bool {
            $searchCriteria = array_merge([$identifier => $input], $criteria);

            return (bool) $repository->findOneBy($searchCriteria);
        });
    }
}
