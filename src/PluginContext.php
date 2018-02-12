<?php

namespace JB000\WordPress;


use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PluginContext
{
	static $instances = [];

	protected $pluginPath;
	protected $viewPath;
	protected $publicUrl;
	protected $configPath;
	protected $name;
	protected $textDomain;

	/**
	 * @var
	 */
	protected $container;

	/**
	 * PluginContainer constructor.
	 *
	 * Creates a new container for the wordpress plugin
	 *
	 * @param      $pluginDir
	 * @param null $name
	 */
	public function __construct($pluginDir,$name = null)
	{
		if($name == null)
			$this->name     = basename($pluginDir);
		else
			$this->name     = $name;

		$this->textDomain   = "text_domain_{$this->name}";
		$this->pluginPath   = $pluginDir.DIRECTORY_SEPARATOR;
		$this->viewPath     = $this->pluginPath . 'resources/views/';
		$this->publicUrl    = '/wp-content/plugins/'.$this->name.'/public/';
		$this->configPath   = $this->pluginPath.'config';

		// create a new container
		$this->container = new Container();
		self::$instances[$this->name] = $this;
		$this->container->instance(Container::class,$this->container);
		$this->container->instance(PluginContext::class,$this);
		$this->container->instance('app',$this);

		//bind dispatcher
		$this->container->bind(Dispatcher::class,function()
		{
			return new \Illuminate\Events\Dispatcher($this->container);
		});

		//bind config
		$config = [];
		foreach(glob($this->configPath.'/*.*') as $file)
		{
			$namespace = pathinfo($file)['filename'];
			$config[$namespace] = require $file;
		}
		$this->container->instance('config',new Repository($config));

		//bind view factory
		$this->container->bind(Factory::class,function()
		{

			$pathsToTemplates = $this->pluginPath.'resources/views';
			$pathToCompiledTemplates = $this->pluginPath.'storage/cache/views';

			$filesystem = new Filesystem;
			$eventDispatcher = $this->container->make(Dispatcher::class);

			$viewResolver = new EngineResolver();

			$viewResolver->register('blade', function () use ($pathToCompiledTemplates, $filesystem) {

				$bladeCompiler = new BladeCompiler($filesystem, $pathToCompiledTemplates);
				return new CompilerEngine($bladeCompiler, $filesystem);
			});

			$viewResolver->register('php', function () {
				return new PhpEngine;
			});


			$viewFinder = new FileViewFinder($filesystem, [$pathsToTemplates]);
			$viewFactory = new \Illuminate\View\Factory($viewResolver, $viewFinder, $eventDispatcher);

			return $viewFactory;

		});

		/*
		 * Bind the Wordpress database
		 */
		global $wpdb;
		$capsule = new Manager();
		$capsule->addConnection([
			'driver' => 'mysql',
			'host' => DB_HOST,
			'database' => DB_NAME,
			'username' => DB_USER,
			'password' => DB_PASSWORD,
			'charset' => $wpdb->charset,
			'collation' => $wpdb->collate,
			'prefix' => $wpdb->prefix,
		],'wordpress');

		//set the event dispatcher
		$capsule->setEventDispatcher($this->container->make(Dispatcher::class));
		$capsule->setAsGlobal();
		$capsule->bootEloquent();

	}

	/**
	 * Renders a view using the Blade template engine
	 *
	 * @param $path
	 * @param $data
	 *
	 * @return mixed
	 */
	public function view($path,$data=[])
	{
		$viewFactory = $this->container->make(Factory::class);
		$data['context'] = $this;
		return $viewFactory->make($path,$data)->render();
	}

	/**
	 * @param null $key
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function config($key = null, $default = null)
	{
		$repo = $this->container()->make('config');
		if (is_null($key)) {
			return $repo;
		}
		if (is_array($key)) {
			return $repo->set($key);
		}
		return $repo->get($key, $default);
	}

	/**
	 * @return Container
	 */
	public function container()
	{
		return $this->container;
	}

	/**
	 * @param $pluginName
	 *
	 * @return PluginContext|null
	 * @throws \Exception
	 */
	public static function instance($pluginName)
	{
		if(array_key_exists($pluginName,self::$instances))
			return self::$instances[$pluginName];

		throw new \Exception("$pluginName does not have a context");
	}

	/**
	 * Gets the plugin path.
	 *
	 * @return string
	 */
	public function pluginPath()
	{
		return $this->pluginPath;
	}

	/**
	 * Gets the public URL.
	 *
	 * @return string
	 */
	public function publicUrl()
	{
		return $this->publicUrl;
	}


	/**
	 * Gets the name of the plugin
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Gets the text domain of the plugin
	 *
	 * @return string
	 */
	public function textDomain()
	{
		return $this->textDomain;
	}

	/**
	 * Method to initialise the container
	 */
	public function run()
	{
		$this->route();
	}

	/**
	 * Load routes and perform routing
	 */
	protected function route()
	{
		// Using Illuminate/Events/Dispatcher here (not required); any implementation of
		// Illuminate/Contracts/Event/Dispatcher is acceptable
		$events = $this->container->make(Dispatcher::class);

		// Create the router instance
		$router = new Router($events, $this->container);

		$request = Request::capture();

		//load the correct route file
		if(is_admin())
		{

			//load admin routes

			//we use a different request here to fix image upload bug
			$request = AdminRequest::capture();

			//bind the request instance
			$this->container->instance(Request::class,$request);

			//load ajax routes if we have the file
			$this->registerAdminAjaxRoutes();

			//require the routes
			require_once $this->pluginPath.'routes/admin.php';

		}
		else if(starts_with($request->path(),'wp-json/'))
		{
			//load api routes

			if(!file_exists($this->pluginPath.'routes/api.php'))
				return;

			require_once $this->pluginPath.'routes/api.php';
		}

		// Dispatch the request through the router
		try
		{
			//get the response
			$router->dispatch($request);

			//we do not send the response back as it will be up to the controller logic to hook into WP actions and display the view
			//$response->send();
		}
		catch(\Exception $ex)
		{
			if($ex instanceof MethodNotAllowedHttpException ||
			   $ex instanceof NotFoundHttpException)
			{
				//do nothing because the wp-admin will handle the route
			}
			else
				throw $ex;
		}
	}

	/**
	 * Will load all admin ajax routes and registered them with wordpress
	 */
	protected function registerAdminAjaxRoutes()
	{
		$file = $this->pluginPath.'routes/admin-ajax.php';

		if(is_admin() && file_exists($file))
		{
			// Create the router instance
			$router = new Router($this->container->make(Dispatcher::class), $this->container);

			//load the routes
			require_once $file;

			//get the request
			$request = AdminRequest::capture();

			//ensure the request is ajax
			if(!$request->ajax())
				return;

			add_action('admin_init', function () use ($router,$request)
			{
				//get the available routes which match request method
				$availableRoutes = $router->getRoutes()->get($request->method());

				//each route must be registered as a action with wordpress
				foreach($availableRoutes as $route)
				{
					add_action('wp_ajax_'.$route->uri,function() use($router,$route,$request)
					{
						//return the response from the route action
						$shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
						                        $this->container->make('middleware.disable') === true;

						$middleware = $shouldSkipMiddleware ? [] : $router->gatherRouteMiddleware($route);
						$content = (new Pipeline($this->container))
							->send($request)
							->through($middleware)
							->then(function ($request) use ($route,$router) {
								$route->bind($request);
								$response = $router->prepareResponse(
									$request, $route->run()
								);
								return $response->content();
							});
						echo $content;

						//kill the script because there is nothing else to do
						die();
					});
				}
			});
		}
	}
}