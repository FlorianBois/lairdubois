<?php

namespace Ladb\CoreBundle\Repository;

use Ladb\CoreBundle\Entity\User;
use Ladb\CoreBundle\Model\ViewableInterface;
use Ladb\CoreBundle\Utils\TypableUtils;

class CommentRepository extends AbstractEntityRepository {

	/////

	public function getDefaultJoinOptions() {
		return array( array( 'inner', 'user', 'u' ) );
	}

	/////

	public function findByEntityTypeAndEntityId($entityType, $entityId) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'ps' ))
			->from($this->getEntityName(), 'c')
			->innerJoin('c.user', 'u')
			->leftJoin('c.pictures', 'ps')
			->where('c.entityType = :entityType')
			->andWhere('c.entityId = :entityId')
			->setParameter('entityType', $entityType)
			->setParameter('entityId', $entityId)
			->orderBy('c.createdAt', 'ASC')
		;

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	public function findByIds(array $ids, $orderBySort = null, $orderByOrder = null) {
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'u', 'ps', 'ua' ))
			->from('LadbCoreBundle:comment', 'c')
			->innerJoin('c.user', 'u')
			->leftJoin('c.pictures', 'ps')
			->leftJoin('u.avatar', 'ua')
			->where($queryBuilder->expr()->in('c.id', $ids))
		;

		if (null !== $orderBySort) {
			$queryBuilder
				->addOrderBy($orderBySort, $orderByOrder);
		}

		try {
			return $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}

	/////

	/*
	 * [
	 * 	[ 'entity' => ENTITY, 'comments' => COMMENTS ],
	 *  ...,
	 * ]
	 */
	public function findPaginedByUserGroupByEntityType(User $user, $offset, $limit) {

		// Retrieve concat comment ids per entity
		$queryBuilder = $this->getEntityManager()->createQueryBuilder();
		$queryBuilder
			->select(array( 'c', 'MAX(c.createdAt) AS mx', 'c.entityType', 'c.entityId', 'count(c.id)', 'GROUP_CONCAT(c.id)' ))
			->from($this->getEntityName(), 'c')
			->where('c.user = :user')
			->groupBy('c.entityType, c.entityId')
			->orderBy('mx', 'DESC')
			->setParameter('user', $user)
			->setFirstResult($offset)
			->setMaxResults($limit)
		;
		try {
			$concatResults = $queryBuilder->getQuery()->getResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
		}

		$items = array();

		foreach ($concatResults as $concatResult) {

			$entityType = $concatResult['entityType'];
			$entityId = $concatResult['entityId'];

			// Retrive related entity
			$entityClassName = TypableUtils::getClassByType($entityType);
			if (is_null($entityClassName)) {
				continue;
			}
			$entityRepository = $this->getEntityManager()->getRepository($entityClassName);
			$entity = $entityRepository->findOneByIdJoinedOn($entityId, $entityRepository->getDefaultJoinOptions());
			if (is_null($entity)) {
				continue;
			}
			if ($entity instanceof ViewableInterface && !$entity->getIsViewable()) {
				continue;
			}

			// Retrieve comments
			$commentCount = $concatResult[1];
			if ($commentCount == 1) {
				$comments = array( $concatResult[0] );
			} else {
				$commentIds = explode(',', $concatResult[2]);
				$comments = $this->findByIds($commentIds, 'c.createdAt', 'ASC');
			}

			$items[] = array(
				'entity'   => $entity,
				'comments' => $comments,
			);

		}

		return $items;
	}

}