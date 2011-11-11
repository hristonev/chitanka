<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Chitanka\LibBundle\Util\String;

#use Symfony\Component\Validator\Constraints;
#use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\PersonRepository")
* @ORM\Table(name="person",
*	indexes={
*		@ORM\Index(name="name_idx", columns={"name"}),
*		@ORM\Index(name="last_name_idx", columns={"last_name"}),
*		@ORM\Index(name="orig_name_idx", columns={"orig_name"}),
*		@ORM\Index(name="country_idx", columns={"country"}),
*		@ORM\Index(name="is_author_idx", columns={"is_author"}),
*		@ORM\Index(name="is_translator_idx", columns={"is_translator"})}
* )
*/
class Person
{
	/**
	* @var integer $id
	* @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
	*/
	private $id;

	/**
	* @var string $slug
	* @ORM\Column(type="string", length=50, unique=true)
	*/
	private $slug;

	/**
	* @var string $name
	* @ORM\Column(type="string", length=100)
	*/
	private $name;

	/**
	* @var string $orig_name
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $orig_name;

	/**
	* @var string $real_name
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $real_name;

	/**
	* @var string $oreal_name
	* @ORM\Column(type="string", length=100, nullable=true)
	*/
	private $oreal_name;

	/**
	* @var string $last_name
	* @ORM\Column(type="string", length=50, nullable=true)
	*/
	private $last_name;

	/**
	* @var string $country
	* @ORM\Column(type="string", length=10)
	*/
	private $country;
	static private $countryList = array(
		'-',
		'au',
		'at',
		'al',
		'ar',
		'am',
		'az',
		'by',
		'be',
		'ba',
		'br',
		'bg',
		'gb',
		've',
		'gt',
		'de',
		'gr',
		'dk',
		'aro',
		'agr',
		'zm',
		'et',
		'il',
		'in',
		'ir',
		'ie',
		'es',
		'it',
		'kz',
		'ca',
		'cn',
		'co',
		'cu',
		'lb',
		'lt',
		'lu',
		'mx',
		'md',
		'ni',
		'nz',
		'no',
		'pa',
		'pe',
		'pl',
		'pt',
		'ro',
		'ru',
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
		'nl',
		'hr',
		'cz',
		'cl',
		'ch',
		'se',
		'yu',
		'za',
		'jp',
	);

	/** @ORM\Column(type="boolean") */
	private $is_author = true;

	/** @ORM\Column(type="boolean") */
	private $is_translator = false;

	/**
	* @var string $info
	* @ORM\Column(type="string", length=160, nullable=true)
	*/
	private $info;

	/**
	* @var integer $person
	* @ORM\ManyToOne(targetEntity="Person", cascade={"remove"})
	*/
	private $person;

	/**
	* @var string $type
	* @ORM\Column(type="string", length=1, nullable=true)
	*/
	private $type;
	static private $typeList = array(
		'p' => 'Псевдоним',
		'r' => 'Истинско име',
		'a' => 'Алтернативно изписване',
	);

	/**
	* @ORM\ManyToMany(targetEntity="Text", mappedBy="authors")
	*/
	private $textsAsAuthor;

	/**
	* @ORM\ManyToMany(targetEntity="Text", mappedBy="translators")
	*/
	private $textsAsTranslator;

	/**
	* @ORM\ManyToMany(targetEntity="Book", mappedBy="authors")
	*/
	private $books;

	/**
	* @ORM\ManyToMany(targetEntity="Series", mappedBy="authors")
	* @ORM\JoinTable(name="series_author")
	*/
	private $series;


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = $slug; }
	public function getSlug() { return $this->slug; }

	public function setName($name)
	{
		$this->name = $name;
		$this->last_name = self::getLastNameFromName($name);
		if (empty($this->slug)) {
			$this->slug = String::slugify($name);
		}
	}
	public function getName() { return $this->name; }

	public function getLastNameFromName($name)
	{
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		return isset($m[2]) ? $m[2] : $name;
	}

	public function setOrigName($origName)
	{
		$this->orig_name = $origName;
		if (empty($this->slug) && preg_match('/[a-z]/', $origName)) {
			$this->slug = String::slugify($origName);
		}
	}
	public function getOrigName() { return $this->orig_name; }
	public function orig_name() { return $this->orig_name; }

	public function setRealName($realName) { $this->real_name = $realName; }
	public function getRealName() { return $this->real_name; }

	public function setOrealName($orealName) { $this->oreal_name = $orealName; }
	public function getOrealName() { return $this->oreal_name; }

	public function getLastName() { return $this->last_name; }

	public function setCountry($country) { $this->country = $country; }
	public function getCountry() { return $this->country; }

	public function getIsAuthor() { return $this->is_author; }
	public function getIsTranslator() { return $this->is_translator; }
	public function is_author() { return $this->is_author; }
	public function is_translator() { return $this->is_translator; }
	public function setIsAuthor($isAuthor) { $this->is_author = $isAuthor; }
	public function setIsTranslator($isTranslator) { $this->is_translator = $isTranslator; }

	public function isAuthor($isAuthor = null)
	{
		if ($isAuthor !== null) {
			$this->is_author = $isAuthor;
		}
		return $this->is_author;
	}

	public function isTranslator($isTranslator = null)
	{
		if ($isTranslator !== null) {
			$this->is_translator = $isTranslator;
		}
		return $this->is_translator;
	}

	public function getRole()
	{
		$roles = array();
		if ($this->is_author) $roles[] = 'author';
		if ($this->is_translator) $roles[] = 'translator';

		return implode(',', $roles);
	}

	public function setInfo($info) { $this->info = $info; }
	public function getInfo() { return $this->info; }

	public function setPerson($person) { $this->person = $person; }
	public function getPerson() { return $this->person; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function getBooks() { return $this->books; }

	public function __toString()
	{
		return $this->name;
	}

	static public function getCountryList()
	{
		return self::$countryList;
	}

	static public function getTypeList()
	{
		return self::$typeList;
	}
}
