<?php

namespace Controller\Controller;
use Controller\Core as Core;

class IndexController 
{
	protected $router;
	protected $request;
	protected $response;

	protected $twig;
	protected $auth;

	public function __construct(Core\Route $route_, Core\Router $router_, Core\Request $request_, Core\Response $response_, $auth_)
	{
		$this->route    = $route_;
		$this->router   = $router_;
		$this->request  = $request_;
		$this->response = $response_;
		$this->auth		= $auth_;

		$loader = new \Twig_Loader_Filesystem(TWIG_VIEWS_DIR);
		$this->twig = new \Twig_Environment($loader, array(
			// Prod only
			// 'cache' => TWIG_VIEWS_DIR.'/cache',
		));

		// Use it to load content (CSS, JS, etc.) with a relative path
		$this->twig->addGlobal('R', $this->request->getRelativePath());
		
		$pathFunction = new \Twig_SimpleFunction('path', function($routeName_) {
			return $this->generateUrl($routeName_);
		});
		$this->twig->addFunction($pathFunction);
	}

	public function generateUrl($routeName_)
	{
		return $this->router->path($routeName_);
	}

	public function checkPermissions()
	{
		if ($this->route->isAuthRequired()) {
			$permissions = $this->route->getPermissions();
			if (!empty($permissions)) { 
				foreach ($permissions as $key => $authorizedGroup) {
					if ($this->auth->getGroup() === $authorizedGroup)
						return true;
				}

				// Unauthorized
				$this->response->addHeader('HTTP/1.1 403 Forbidden')->send();
				echo $this->twig->render('./errors/403.html.twig', array());
				exit;
			}
		}
		return true;
	}

	public function error404Action()
	{
		$this->response->addHeader($_SERVER['SERVER_PROTOCOL'].' 404 Not Found')->send();
		echo $this->twig->render('./errors/404.html.twig', array());
		exit;
	}

	public function redirect($routeName_)
	{
		$this->response->addHeader('Location: '.$this->generateUrl($routeName_));
		$this->response->send();
		exit;
	}
}
