<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseRepository extends EntityRepository
{
    public function sort(Request $request, QueryBuilder $qb): QueryBuilder
    {
        $class = $qb->getDQLParts()['from'][0]->getFrom();
        $em = $this->getEntityManager();
        $alias = current($qb->getRootAliases());

        $sort = $request->query->get('sort', null);
        if (!$sort) {
            return $qb;
        }

        // check if sort format is valid
        $sortArray = explode(',', trim($sort));
        if (2 !== \count($sortArray) || !$sortArray[1]
                    |> strtolower(...)
                    |> trim(...)
                    |> (static fn($x) => \in_array($x, ['asc', 'desc'], true))) {
            return $qb;
        }

        $field = $sortArray[0];
        $order = $sortArray[1];

        $associationField = strpos($field, '__');

        if (!$associationField) {
            $field = $this->convertToCamelCase($field);
            if (!$em->getClassMetadata($class)->hasField($field)) {
                throw new NotFoundHttpException(\sprintf('No field found: %s', $field));
            }

            return $qb->orderBy(\sprintf('%s.%s', $alias, $field), $order);
        }

        $associationArr = explode('__', trim($sort));
        $association = $associationArr[\count($associationArr) - 2];
        $association = $this->convertToCamelCase($association);
        $associationKey = explode(',', trim($associationArr[\count($associationArr) - 1]))[0];
        $associationKey = $this->convertToCamelCase($associationKey);

        $joinDqlParts = $qb->getDQLParts()['join'];
        $alreadyJoined = false;
        $joinMapping = [];
        foreach ($joinDqlParts as $joins) {
            foreach ($joins as $join) {
                $joinedKey = explode('.', trim((string) $join->getJoin()))[1];
                $joinMapping[$joinedKey] = $join->getAlias();
                if ($joinedKey === $association) {
                    $alreadyJoined = true;
                }
            }
        }

        if (!$alreadyJoined) {
            $length = 1;
            $joinAlias = substr($association, 0, $length);

            if (\in_array($joinAlias, $joinMapping, true)) {
                $joinAlias = substr($association, 0, ++$length);
            }

            return $qb->join(\sprintf('%s.%s', $alias, $association), $joinAlias)
                ->orderBy(\sprintf('%s.%s', $joinAlias, $associationKey), $order);
        }

        return $qb->orderBy(\sprintf('%s.%s', $joinMapping[$association], $associationKey), $order);
    }

    public function sanitizeLike(string $search): string
    {
        return addcslashes($search, '_"');
    }

    protected function getOrAddJoinAlias(
        QueryBuilder $qb,
        string $path,
        string $alias,
        string $joinType = 'inner'
    ): string {
        $rootAlias = $qb->getRootAliases()[0];
        $joinParts = $qb->getDQLPart('join');

        if (isset($joinParts[$rootAlias])) {
            foreach ($joinParts[$rootAlias] as $join) {
                if ($join->getJoin() === $path) {
                    return $join->getAlias();
                }
            }
        }

        if ('inner' === $joinType) {
            $qb->join($path, $alias);
        }

        if ('left' === $joinType) {
            $qb->leftJoin($path, $alias);
        }

        return $alias;
    }

    private function convertToCamelCase($string): string
    {
        if (strpos((string)$string, '_')) {
            $string = (static fn($x): array|string => str_replace('_', '', $x))
                    |> ucwords((string)$string, '_')
                                        |> lcfirst(...);
        }

        return $string;
    }
}
