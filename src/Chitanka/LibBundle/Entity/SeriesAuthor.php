<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity
* @ORM\Table(name="series_author",
*	uniqueConstraints={@ORM\UniqueConstraint(name="person_series_uniq", columns={"person_id", "series_id"})}
* )
*/
class SeriesAuthor extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer $person
	 * @ORM\ManyToOne(targetEntity="Person")
	 */
	private $person;

	/**
	 * @var integer $series
	 * @ORM\ManyToOne(targetEntity="Series", inversedBy="seriesAuthors")
	 */
	private $series;

	public function getId() { return $this->id; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setSeries($series) { $this->series = $series; }
	public function getSeries() { return $this->series; }

}
