<?php

namespace Chitanka\LibBundle\Entity;

/**
 *
 */
class WorkEntryRepository extends EntityRepository
{
	public function getLatest($limit = null)
	{
		return $this->getByIds($this->getLatestIdsByDate($limit), 'e.date DESC');
	}

	public function getLatestIdsByDate($limit = null)
	{
		$dql = sprintf('SELECT e.id FROM %s e WHERE e.deleted_at IS NULL ORDER BY e.date DESC', $this->getEntityName());
		$query = $this->_em->createQuery($dql)->setMaxResults($limit);

		return $query->getResult('id');
	}


	public function getActiveEntities($page = 0, $limit = null)
	{
// 		$dql = "SELECT b FROM {$this->getEntityName()} b WHERE b.deleted_at IS NULL ORDER BY b.date DESC";
// 		$query = $this->setPagination($this->_em->createQuery($dql), $page, $limit);
// 		return $query->getResult();
		return $this->_em->createQueryBuilder('we.date DESC')
			->select('we', 'u')
			->from($this->getEntityName(), 'we')
			->leftJoin('we.user', 'u')
// 			->leftJoin('we.contribs', 'c')
			->where('we.deleted_at IS NULL')
			->getQuery()
			->getResult();
	}


	public function getByTitleOrAuthor($title, $limit = null)
	{
		return $this->getQueryBuilder()
			->where('e.deleted_at IS NULL')
			->andWhere('e.title LIKE ?1 OR e.author LIKE ?1')
			->setParameter(1, "%$title%")
			->getQuery()
			->getArrayResult();
	}

	public function findOlderThan($date)
	{
		return $this->getQueryBuilder()
			->where('e.deleted_at IS NULL')
			->andWhere('e.date < ?1')
			->setParameter(1, $date)
			->getQuery()
			->getResult();
	}

	public function getQueryBuilder($orderBys = null)
	{
		$qb = parent::getQueryBuilder($orderBys)
			->select('e', 'u', 'c', 'cu')
			->leftJoin('e.user', 'u')
			->leftJoin('e.contribs', 'c')
			->leftJoin('c.user', 'cu')
			->where('e.deleted_at IS NULL');

		return $qb;
	}

}
