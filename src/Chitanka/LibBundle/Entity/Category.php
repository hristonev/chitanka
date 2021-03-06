<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Chitanka\LibBundle\Util\String;

/**
 * @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\CategoryRepository")
 * @ORM\Table(name="category")
 * @UniqueEntity(fields="slug", message="This slug is already in use.")
 * @UniqueEntity(fields="name")
 */
class Category extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var string $slug
	 * @ORM\Column(type="string", length=50, unique=true)
	 * @Assert\NotBlank
	 */
	private $slug = '';

	/**
	 * @var string $name
	 * @ORM\Column(type="string", length=80, unique=true)
	 * @Assert\NotBlank
	 */
	private $name = '';

	/**
	 * @var integer $parent
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
	 */
	private $parent;

	/**
	 * The children of this category
	 * @var array
	 * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
	 * @ORM\OrderBy({"name" = "ASC"})
	 */
	private $children;

	/**
	 * @var array
	 * @ORM\OneToMany(targetEntity="Book", mappedBy="category")
	 * @ORM\OrderBy({"title" = "ASC"})
	 */
	private $books;

	/**
	 * Number of books in this category
	 * @var integer
	 * @ORM\Column(type="integer")
	 */
	private $nr_of_books = 0;


	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setName($name) { $this->name = $name; }
	public function getName() { return $this->name; }

	public function setParent($parent) { $this->parent = $parent; }
	public function getParent() { return $this->parent; }

	public function setChildren($children) { $this->children = $children; }
	public function getChildren() { return $this->children; }

	public function setBooks($books) { $this->books = $books; }
	public function getBooks() { return $this->books; }

	public function setNrOfBooks($nr_of_books) { $this->nr_of_books = $nr_of_books; }
	public function getNrOfBooks() { return $this->nr_of_books; }
	public function incNrOfBooks($value = 1)
	{
		$this->nr_of_books += $value;
	}

	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Get all ancestors
	 *
	 * @return array
	 */
	public function getAncestors()
	{
		$ancestors = array();
		$category = $this;
		while (null !== ($parent = $category->getParent())) {
			$ancestors[] = $parent;
			$category = $parent;
		}

		return $ancestors;
	}

	public function getDescendantIdsAndSelf()
	{
		return array_merge(array($this->getId()), $this->getDescendantIds());
	}

	/**
	 * Get all descendants
	 *
	 * @return array
	 */
	public function getDescendantIds()
	{
		$ids = array();
		foreach ($this->getChildren() as $category) {
			$ids[] = $category->getId();
			$ids = array_merge($ids, $category->getDescendantIds());
		}

		return $ids;
	}
}
