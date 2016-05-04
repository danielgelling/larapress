<?php

namespace Larapress\Illuminate\Routing;

use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Larapress\Illuminate\Routing\Route;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router as BaseRouter;
use Larapress\Illuminate\Foundation\Http\Response;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Router extends BaseRouter
{
   /**
     * Create a new Router instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Container\Container  $container
     * @return void
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        $this->events = $events;
        $this->routes = new RouteCollection;
        $this->container = $container ?: new Container;

        $this->bind('_missing', function ($v) {
            return explode('/', $v);
        });
    }


    /**
     * Create a new route instance.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function createRoute($methods, $uri, $action)
    {
        // If the route is routing to a controller we will parse the route action into
        // an acceptable array format before registering it and creating this route
        // instance itself. We need to build the Closure that will call this out.
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        $route = $this->newRoute(
            $methods, $this->prefix($uri), $action
        );

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))
                    ->setRouter($this)
                    ->setContainer($this->container);
    }

    /**
     * Dispatch the request to a route and return the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function dispatchToRoute(Request $request)
    {
        // First we will find a route that matches this request. We will also set the
        // route resolver on the request so middlewares assigned to the route will
        // receive access to this route instance for checking of the parameters.
        $route = $this->findRoute($request);

        // No route, let Wordpress handle it
        if(is_null($route))
            return;

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $this->events->fire(new \Illuminate\Routing\Events\RouteMatched($route, $request));

        $response = $this->runRouteWithinStack($route, $request);

        return $this->prepareResponse($request, $response);
    }

    /**
     * Find the route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     */
    protected function findRoute($request)
    {
        try {
            $this->current = $route = $this->routes->match($request);

            $this->container->instance('Illuminate\Routing\Route', $route);

            return $this->substituteBindings($route);
        } catch (NotFoundHttpException $e) {
            return;
        }
    }

     /**
     * Register a new GET route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function get($uri, $action = null)
    {
        $this->route = $this->addRoute(['GET', 'HEAD'], $uri, $action);

        return $this;
    }

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function post($uri, $action = null)
    {
        $this->route = $this->addRoute('POST', $uri, $action);

        return $this;
    }

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function put($uri, $action = null)
    {
        $this->route = $this->addRoute('PUT', $uri, $action);

        return $this;
    }

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function patch($uri, $action = null)
    {
        $this->route = $this->addRoute('PATCH', $uri, $action);

        return $this;
    }

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function delete($uri, $action = null)
    {
        $this->route = $this->addRoute('DELETE', $uri, $action);

        return $this;
    }

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function options($uri, $action = null)
    {
        $this->route = $this->addRoute('OPTIONS', $uri, $action);

        return $this;
    }

    /**
     * Register a new route responding to all verbs.
     *
     * @param  string  $uri
     * @param  \Closure|array|string|null  $action
     * @return \Illuminate\Routing\Route
     */
    public function any($uri, $action = null)
    {
        $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

        $this->route = $this->addRoute($verbs, $uri, $action);

        return $this;
    }









    public function addToMenu($name, $parent = null)
    {
        if (\App::make('request')->server->get('SCRIPT_NAME') === 'artisan')
            return;

        $action = $this->route->getAction();
        $uri = $this->route->getUri();

        if (is_null($parent)) {
            \add_action('admin_menu', function () use ($name, $uri, $action) {
                add_menu_page('My Cool Plugin Settings', $name, 'administrator', $uri, function () use ($action) {
                    list($class, $method) = explode('@', $action['uses']);
                    echo (new $class)->$method();
                });
            });
        } else {
            $parent = $this->routes->getByName($parent);

            \add_action('admin_menu', function () use ($name, $uri, $action, $parent) {
                add_submenu_page($parent->getUri(), 'My Cool Plugin Settings', $name, 'administrator',$uri, function () use ($action) {
                    list($class, $method) = explode('@', $action['uses']);
                    echo (new $class)->$method();
                });
            });
        }
    }

    public function menu($name, $action)
    {
        $this->uri = $uri = $this->slugify($name);

        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        if (\App::make('request')->server->get('SCRIPT_NAME') !== 'artisan') {
            \add_action('admin_menu', function () use ($name, $uri, $action) {
                add_menu_page('My Cool Plugin Settings', $name, 'administrator', $uri, function () use ($action) {
                    list($class, $method) = explode('@', $action['uses']);
                    echo (new $class)->$method();
                });
            });
        }

        $action = function () { return; };

        $this->routes->add($this->createRoute(['GET', 'HEAD'], $uri, $action));

        return $this;
    }

    public function item($name, $action)
    {
        $uri = $this->slugify($name);

        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        if (\App::make('request')->server->get('SCRIPT_NAME') !== 'artisan') {
            $scope = $this;

            \add_action('admin_menu', function () use ($scope, $name, $uri, $action) {
                add_submenu_page($scope->uri, 'My Cool Plugin Settings', $name, 'administrator', $this->uri . '&subpage=' . $uri, function () use ($action) {
                    list($class, $method) = explode('@', $action['uses']);
                    echo (new $class)->$method();
                });
            });
        }

        $action = function () { return; };

        $this->routes->add($this->createRoute(['GET', 'HEAD'], $uri, $action));

        return $this;
    }

    public function route($method, $uri, $action)
    {
        if ($this->actionReferencesController($action)) {
            $action = $this->convertToControllerAction($action);
        }

        if (\App::make('request')->server->get('SCRIPT_NAME') !== 'artisan') {
            $scope = $this;

            \add_action('admin_menu', function () use ($scope, $uri, $action) {
                add_utility_page($scope->uri, 'My Cool Plugin Settings', 'administrator', $this->uri . '&subpage=' . $uri, function () use ($action) {
                    list($class, $method) = explode('@', $action['uses']);
                    echo (new $class)->$method();
                });
            });
        }

        $action = function () { return; };

        $this->routes->add($this->createRoute(['GET', 'HEAD'], $uri, $action));

        return $this;
    }

    public function slugify($slug)
    {
        // replace non letter or digits by -
        $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);

        // transliterate
        $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);

        // remove unwanted characters
        $slug = preg_replace('~[^-\w]+~', '', $slug);

        // trim
        $slug = trim($slug, '-');

        // remove duplicate -
        $slug = preg_replace('~-+~', '-', $slug);

        // lowercase
        $slug = strtolower($slug);

        return $slug;
    }
}
