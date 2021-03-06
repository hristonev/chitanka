<?php

namespace Chitanka\LibBundle\Entity;

/**
 *
 */
class PersonRepository extends EntityRepository
{
	protected $asAuthor = false;
	protected $asTranslator = false;
	protected $queryableFields = array('id', 'slug', 'name', 'orig_name', 'real_name', 'oreal_name');

	private $countryList = array(
		'-',
		'au',
		'at',
		'al',
		'ar',
		'am',
		'az',
		'bb',
		'by',
		'be',
		'ba',
		'bo',
		'br',
		'bg',
		'gb',
		've',
		'gt',
		'de',
		'gr',
		'dk',
		'dm',
		'aro',
		'agr',
		'zm',
		'ec',
		'ee',
		'et',
		'il',
		'in',
		'ir',
		'ie',
		'es',
		'it',
		'kz',
		'ca',
		'kg',
		'cn',
		'co',
		'cr',
		'cu',
		'lb',
		'lt',
		'lu',
		'mq',
		'mx',
		'md',
		'ni',
		'nz',
		'no',
		'pa',
		'py',
		'pe',
		'pl',
		'pt',
		'pr',
		'ro',
		'ru',
		'sv',
		'us',
		'sk',
		'si',
		'rs',
		'tr',
		'ua',
		'hu',
		'uy',
		'fr',
		'fi',
		'ht',
		'nl',
		'hn',
		'hr',
		'cz',
		'cl',
		'ch',
		'se',
		'yu',
		'za',
		'jm',
		'jp',
	);

	private $typeList = array(
		'p' => 'Псевдоним',
		'r' => 'Истинско име',
		'a' => 'Алтернативно изписване',
	);

	public function getCountryList()
	{
		return $this->countryList;
	}

	public function getTypeList()
	{
		return $this->typeList;
	}

	public function findBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}

	public function getBy($filters, $page = 1, $limit = null)
	{
		$query = $this->setPagination($this->getQueryBy($filters), $page, $limit);

		return $query->getArrayResult();
	}

	public function countBy($filters)
	{
		return $this->getCountQueryBy($filters)->getSingleScalarResult();
	}

	public function getBySlug($slug)
	{
		return $this->findOneBy(array('slug' => $slug));
	}


	public function getQueryBy($filters)
	{
		$qb = $this->getQueryBuilder();
		$qb = $this->addFilters($qb, $filters);

		return $qb->getQuery();
	}

	public function getCountQueryBy($filters)
	{
		$qb = $this->getCountQueryBuilder();
		$qb = $this->addFilters($qb, $filters);

		return $qb->getQuery();
	}


	public function getQueryBuilder($orderBys = null)
	{
		$qb = $this->getBaseQueryBuilder('e')
			->select('e', 'p')
			->leftJoin('e.person', 'p');

		return $qb;
	}

	public function getCountQueryBuilder($alias = 'e')
	{
		$qb = $this->getBaseQueryBuilder($alias)->select('COUNT(e.id)');

		return $qb;
	}

	public function getBaseQueryBuilder($alias = 'e')
	{
		$qb = $this->createQueryBuilder($alias);
		if ($this->asAuthor) {
			$qb->andWhere("e.is_author = 1");
		}
		if ($this->asTranslator) {
			$qb->andWhere("e.is_translator = 1");
		}

		return $qb;
	}

	public function getCount($where = null)
	{
		$qb = $this->getCountQueryBuilder();
		if ($where) $qb->andWhere($where);

		return $qb->getQuery()->getSingleScalarResult();
	}

	public function getCountsByCountry()
	{
		return $this->getCountQueryBuilder()
			->select('e.country', 'COUNT(e.id)')
			->groupBy('e.country')
			->getQuery()->getResult('key_value');
	}


	public function getByNames($name, $limit = null)
	{
		$q = $this->getQueryBuilder()
			->where('e.name LIKE ?1 OR e.orig_name LIKE ?1 OR e.real_name LIKE ?1 OR e.oreal_name LIKE ?1')
			->setParameter(1, $this->stringForLikeClause($name))
			->getQuery();
		if ($limit) {
			$q->setMaxResults($limit);
		}
		return $q->getArrayResult();
	}


	public function asAuthor()
	{
		$this->asAuthor = true;
		$this->asTranslator = false;

		return $this;
	}

	public function asTranslator()
	{
		$this->asTranslator = true;
		$this->asAuthor = false;

		return $this;
	}


	public function addFilters($qb, $filters)
	{
		$nameField = empty($filters['by']) || $filters['by'] == 'first-name' ? 'e.name' : 'e.last_name';
		$qb->addOrderBy($nameField);
		if ( ! empty($filters['prefix']) && $filters['prefix'] != '-' ) {
			$qb->andWhere("$nameField LIKE :name")->setParameter('name', $filters['prefix'].'%');
		}

		if ( ! empty($filters['country']) ) {
			$qb->andWhere('e.country = ?1')->setParameter(1, $filters['country']);
		}

		return $qb;
	}
}
