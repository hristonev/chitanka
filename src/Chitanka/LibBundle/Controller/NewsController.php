<?php

namespace Chitanka\LibBundle\Controller;

class NewsController extends Controller
{
	public function indexAction($page)
	{
		$_REQUEST['page'] = $page;

		return $this->legacyPage('News');
	}

}
