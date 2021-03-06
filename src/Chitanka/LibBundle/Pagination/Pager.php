<?php

namespace Chitanka\LibBundle\Pagination;

class Pager
{
	private $page = 1;
	private $limit = 30;
	private $total = 0;
	private $count = 0;

	public function __construct($options)
	{
		$fields = array('page', 'limit', 'total');
		foreach ($fields as $field) {
			if (isset($options[$field])) {
				$this->$field = $options[$field];
			}
		}

		$this->count = ceil($this->total / $this->limit);
		if ($this->page > $this->count) {
			$this->page = $this->count;
		}
	}

	public function page()
	{
		return $this->page;
	}

	public function count()
	{
		return $this->count;
	}

	public function show()
	{
		return $this->count > 1;
	}

	public function has_prev()
	{
		return $this->page > 1;
	}

	public function prev()
	{
		return max($this->page - 1, 1);
	}

	public function next()
	{
		return min($this->page + 1, $this->count);
	}

	public function has_next()
	{
		return $this->page < $this->count;
	}


	public function pages()
	{
		$pages = array();
		$first = 1;
		$selected = max($this->page, $first);
		$start = $first; $end = min($first + 2, $this->count);
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$start = max($first, $selected - 2);
		$end = min($selected + 2, $this->count);
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$start = max($first, $this->count - 2);
		$end = $this->count;
		for ($i = $start; $i <= $end; $i++) {
			$pages[$i] = false;
		}

		$pages[$selected] = true;

		return $pages;
	}

}
