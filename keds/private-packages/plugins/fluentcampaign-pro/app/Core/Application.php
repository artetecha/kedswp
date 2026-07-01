<?php
/**
 * @package WPFluent
 * @author  Sheikh Heera <heera.sheikh77@gmail.com> (https://heera.it)
 * @author  Sheikh Heera <mail@heera.it>
 * @author  Sheikh Heera <heera@authlab.io>
 * @link    https://github.com/wpfluent/micro/
 * @license MIT https://opensource.org/licenses/MIT
 * @license GPL-2.0-or-later https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace FluentCampaign\App\Core;

use ArrayAccess;
use InvalidArgumentException;
use FluentCrm\Framework\Support\Facade;
use FluentCrm\Framework\Foundation\Config;

class Application implements ArrayAccess
{
    /**
     * Underlying framework application/container.
     *
     * @var mixed
     */
    protected $app = null;

    /**
     * Main plugin file path.
     *
     * @var string|null
     */
    protected $file = null;

    /**
     * Base plugin URL.
     *
     * @var string|null
     */
    protected $baseUrl = null;

    /**
     * Base plugin filesystem path.
     *
     * @var string|null
     */
    protected $basePath = null;

    /**
     * Local service bindings.
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Methods passed through to the underlying container.
     *
     * @var array
     */
    protected $passthru = [
        'addAction',
        'addFilter',
        'addShortcode'
    ];

    /**
     * Cached composer.json contents.
     *
     * @var array|null
     */
    protected static $composer = null;

    /**
     * Application constructor.
     *
     * @param mixed  $app  Framework container
     * @param string $file Plugin main file
     */
    public function __construct($app, $file)
    {
        $this->init($app, $file);
        $this->setAppLevelNamespace();
        $this->bootstrapApplication();
        $this->registerFacadeResolver($this);
    }

    /**
     * Initialize core properties.
     *
     * @param mixed  $app
     * @param string $file
     * @return void
     */
    protected function init($app, $file)
    {
        $this->app      = $app;
        $this->file     = $file;
        $this->basePath = plugin_dir_path($file);
        $this->baseUrl  = plugin_dir_url($file);
    }

    /**
     * Set application-level namespace from composer.json.
     *
     * @return void
     */
    protected function setAppLevelNamespace()
    {
        $composer = $this->getComposer();

        $this->bindings['__namespace__'] =
            $composer['extra']['wpfluent']['namespace']['current'];
    }

    /**
     * Load composer.json contents.
     *
     * @param  string|null $section
     * @return array
     */
    public function getComposer($section = null)
    {
        if (is_null(static::$composer)) {
            static::$composer = json_decode(
                file_get_contents($this->basePath . 'composer.json'),
                true
            );
        }

        return $section ? static::$composer[$section] : static::$composer;
    }

    /**
     * Bootstrap all application components.
     *
     * @return void
     */
    protected function bootstrapApplication()
    {
        $this->bindAppInstance();
        $this->bindPathsAndUrls();
        $this->loadConfigIfExists();
        $this->registerTextdomain();
        $this->requireCommonFiles($this);
    }

    /**
     * Bind the application instance into the container.
     *
     * @return void
     */
    protected function bindAppInstance()
    {
        App::setInstance($this);
        $this->instance('app', $this);
        $this->instance(__CLASS__, $this);
    }

    /**
     * Bind filesystem paths and URLs.
     *
     * @return void
     */
    protected function bindPathsAndUrls()
    {
        $this->bindUrls();
        $this->basePaths();
    }

    /**
     * Bind asset URLs.
     *
     * @return void
     */
    protected function bindUrls()
    {
        $this->bindings['url.assets'] = $this->baseUrl . 'assets/';
    }

    /**
     * Bind filesystem paths.
     *
     * @return void
     */
    protected function basePaths()
    {
        $this->bindings['path']             = $this->basePath;
        $this->bindings['path.app']         = $this->basePath . 'app/';
        $this->bindings['path.hooks']       = $this->bindings['path.app'] . 'Hooks/';
        $this->bindings['path.http']        = $this->bindings['path.app'] . 'Http/';
        $this->bindings['path.controllers'] = $this->bindings['path.http'] . 'Controllers/';
        $this->bindings['path.config']      = $this->basePath . 'config/';
        $this->bindings['path.assets']      = $this->basePath . 'assets/';
        $this->bindings['path.resources']   = $this->basePath . 'resources/';
        $this->bindings['path.views']       = $this->bindings['path.app'] . 'Views/';
    }

    /**
     * Load configuration files if available.
     *
     * @return void
     */
    protected function loadConfigIfExists()
    {
        $data = [];

        if (is_dir($this['path.config'])) {
            foreach (glob($this['path.config'] . '*.php') as $file) {
                $data[basename($file, '.php')] = require_once $file;
            }
        }

        $data['app']['rest_namespace'] =
            $this->app->config->get('app.rest_namespace');

        $this->bindings['config'] = new Config($data);
    }

    /**
     * Register plugin textdomain.
     *
     * @return void
     */
    protected function registerTextdomain()
    {
        $this->app->addAction('init', function () {
            load_plugin_textdomain(
                $this->config->get('app.text_domain'),
                false,
                $this->textDomainPath()
            );
        });
    }

    /**
     * Resolve textdomain path.
     *
     * @return string
     */
    protected function textDomainPath()
    {
        return basename($this->bindings['path']) .
            $this->config->get('app.domain_path');
    }

    /**
     * Require common hook and bootstrap files.
     *
     * @param Application $app
     * @return void
     */
    protected function requireCommonFiles($app)
    {
        require_once $this->basePath . 'app/Hooks/actions.php';
        require_once $this->basePath . 'app/Hooks/filters.php';

        if (file_exists($bindings = $this->basePath . 'boot/bindings.php')) {
            require_once $bindings;
        }

        if (file_exists($includes = $this->basePath . 'app/Hooks/includes.php')) {
            require_once $includes;
        }

        $this->registerRestRoutes($app->app);
    }

    /**
     * Register REST API routes.
     *
     * @param mixed $app
     * @return void
     */
    protected function registerRestRoutes($app)
    {
        $app->addAction('rest_api_init', function () use ($app) {
            try {
                $app->router->registerRoutes(
                    $this->requireRouteFile($app->router)
                );
            } catch (InvalidArgumentException $e) {
                return $app->response->json([
                    'message' => $e->getMessage()
                ], $e->getCode() ?: 500);
            }
        });
    }

    /**
     * Load route definitions.
     *
     * @param mixed $router
     * @return mixed
     */
    protected function requireRouteFile($router)
    {
        if (file_exists($this['path.http'] . 'Routes/routes.php')) {
            return require_once $this['path.http'] . 'Routes/routes.php';
        }

        $router->namespace(
            $this->bindings['__namespace__'] . '\App\Http\Controllers'
        )->group(function ($router) {
            require_once $this['path.http'] . 'Routes/api.php';
        });
    }

    /**
     * Register facade resolver.
     *
     * @param Application $app
     * @return void
     */
    protected function registerFacadeResolver($app)
    {
        Facade::setFacadeApplication($app);

        spl_autoload_register(function ($class) use ($app) {
            $ns = substr(__NAMESPACE__, 0, strpos(__NAMESPACE__, '\\'));

            if (str_contains($class, $ns . '\Facade')) {
                $this->createFacadeFor($ns . '\Facade', $class, $app);
            }
        });
    }

    /**
     * Create a dynamic facade class.
     *
     * @param string $facade
     * @param string $class
     * @param mixed  $app
     * @return void
     */
    protected function createFacadeFor($facade, $class, $app)
    {
        $facadeAccessor = $this->resolveFacadeAccessor($facade, $class, $app);

        $anonymousClass = new class($facadeAccessor) extends Facade {
            protected static $facadeAccessor;

            public function __construct($facadeAccessor)
            {
                static::$facadeAccessor = $facadeAccessor;
            }

            protected static function getFacadeAccessor()
            {
                return static::$facadeAccessor;
            }
        };

        class_alias(get_class($anonymousClass), $class, true);
    }

    /**
     * Resolve facade accessor name.
     *
     * @param string $facade
     * @param string $class
     * @param mixed  $app
     * @return string|null
     */
    protected function resolveFacadeAccessor($facade, $class, $app)
    {
        $name = strtolower(trim(str_replace($facade, '', $class), '\\'));

        if ($name === 'route') {
            $name = 'router';
        }

        return $app->bound($name) ? $name : null;
    }

    /**
     * Determine application environment.
     *
     * @return string
     */
    public function env()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'dev';
        }

        return $this->config->get('app.env');
    }

    /* -----------------------------------------------------------------
     | ArrayAccess & Magic Methods
     |----------------------------------------------------------------- */

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->bindings[$key]) ?: $this->app->offsetExists($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($key === 'view') {
            return $this->view;
        }

        return $this->bindings[$key] ?? $this->app->make($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->app->offsetSet($key, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        $this->app->offsetUnset($key);
    }

    public function __get($key)
    {
        if ($key === 'view') {
            $view = $this->app->make($key);
            $view->setViewPath($this->bindings['path.views']);
            return $view;
        }

        return $this->bindings[$key] ?? $this->app[$key];
    }

    public function __set($key, $value)
    {
        $this->app[$key] = $value;
    }

    public function __call($method, $params)
    {
        if ($method === 'make' && in_array('view', $params)) {
            return $this->view;
        }

        if (in_array($method, $this->passthru)) {
            if (is_string($params[1]) && !$this->app->hasNamespace($params[1])) {
                $ns = substr(__NAMESPACE__, 0, strpos(__NAMESPACE__, '\\'));
                $params[1] = $ns . '\App\Hooks\Handlers\\' . $params[1];
            }
        }

        return call_user_func_array([$this->app, $method], $params);
    }
}
