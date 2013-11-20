<?php

namespace Chitanka\LibBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Common\Util\Debug;
class RssFeedController extends Controller
{
	public function indexAction()
	{
		$_format = 'html';
		return $this->display("list_rss.$_format");
	}
}