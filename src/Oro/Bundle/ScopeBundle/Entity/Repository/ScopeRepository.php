<?php

namespace Oro\Bundle\ScopeBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class ScopeRepository extends EntityRepository
{
    const IS_NOT_NULL = 'IS_NOT_NULL';

    /**
     * @param array $criteria
     * @return BufferedQueryResultIterator|Scope[]
     */
    public function findByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull('scope.' . $field));
            } else {
                if ($value === self::IS_NOT_NULL) {
                    $qb->andWhere($qb->expr()->isNotNull('scope.' . $field));
                } else {
                    $qb->andWhere($qb->expr()->eq('scope.' . $field, $value));
                }
            }
        }

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param array $criteria
     * @return Scope
     */
    public function findOneByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('scope');
        foreach ($criteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull('scope.' . $field));
            }
            if ($value === self::IS_NOT_NULL) {
                $qb->andWhere($qb->expr()->isNotNull('scope.' . $field));
            } else {
                $qb->andWhere($qb->expr()->eq('scope.' . $field, $value));
            }
        }
        $qb->setMaxResults(1);

        return $qb->getQuery()->getSingleResult();
    }
}
