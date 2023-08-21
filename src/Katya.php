<?php declare(strict_types = 1);
/**
 * @author    Luis Arturo Rodríguez
 * @copyright Copyright (c) 2022 Luis Arturo Rodríguez <rguezque@gmail.com>
 * @link      https://github.com/rguezque
 * @license   https://opensource.org/licenses/MIT    MIT License
 */

namespace rguezque;

use Closure;
use rguezque\Exceptions\{
    RouteNotFoundException,
    UnsupportedRequestMethodException
};

/**
 * Router
 * 
 * @method Route route(string $verb, string $path, callable $closure) Route definition
 * @method Route get(string $path, callable $closure) Shortcut to add route with GET method
 * @method Route post(string $path, callable $closure) Shortcut to add route with POST method
 * @method Route put(string $path, callable $closure) Shortcut to add route with PUT method
 * @method Route patch(string $path, callable $closure) Shortcut to add route with PATCH method
 * @method Route delete(string $path, callable $closure) Shortcut to add route with DELETE method
 * @method Route any(string $path, callable $closure) Shortcut to add route with any method
 * @method void default(Closure $closure) Default controller to exec if don't match any route. Match any request method
 * @method Group group(string $prefix, Closure $closure) Routes group definition under a common prefix
 * @method Katya cors(array $allowed_origins) Set the allowed cross-origins resources sharing
 * @method Katya useServices(Services $services) Set services to use into controllers
 * @method void run(Request $request) Start the router
 * @method void setVar(string $name, $value) Set a variable
 * @method mixed getVar(string $name, $default = null) return a variable by name
 */
class Katya {

    /**
     * Supported verbs
     * 
     * @var string[]
     */
    private const SUPPORTED_VERBS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * GET constant
     * 
     * @var string
     */
    const GET = 'GET';

    /**
     * POST constant
     * 
     * @var string
     */
    const POST = 'POST';

    /**
     * PUT constant
     * 
     * @var string
     */
    const PUT = 'PUT';

    /**
     * PATCH constant
     * 
     * @var string
     */
    const PATCH = 'PATCH';

    /**
     * DELETE constant
     * 
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * Routes collection
     * 
     * @var Route[]
     */
    private $routes = [];

    /**
     * Route groups collection
     * 
     * @var array
     */
    private $groups = [];

    /**
     * Services collection
     * 
     * @var Services
     */
    private $services = null;

    /**
     * Basepath if the router lives into subdirectory
     * 
     * @var string
     */
    private $basepath;

    /**
     * Default directory for search views templates
     * 
     * @var string
     */
    private $viewspath;

    /** 
     * Default controller to exec
     * 
     * @var Closure
     */
    private $default_controller;

    /**
     * Variables collection
     * 
     * @var array
     */
    private $vars = [];

    private $origins = [];

    /**
     * Configure the router options
     * 
     * @param array $options Set basepath and viewspath
     */
    public function __construct(array $options = []) {
        $this->basepath = isset($options['basepath']) ? pathformat($options['basepath']) : '';
        $this->viewspath = isset($options['viewspath']) ? trim($options['viewspath'], '/\\ ').'/' : '';
    }

    /**
     * Set the allowed cross-origins resources sharing
     * 
     * @param string[] Array with allowed origins (allow regex). Ej: '(http(s)://)?(www\.)?localhost:3000'
     * @return Katya
     */
    public function cors(array $allowed_origins): Katya {
        $this->origins = $allowed_origins;

        return $this;
    }

    /**
     * Set services to use into controllers
     * 
     * @param Services $services Service object
     * @return Katya
     */
    public function useServices(Services $services): Katya {
        $this->services = $services;

        return $this;
    }

    /**
     * Shortcut to add route that match any method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function any(string $path, callable $controller): Route {
        $route = $this->route('ANY', $path, $controller);

        return $route;
    }

    /**
     * Shortcut to add route with GET method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function get(string $path, callable $controller): Route {
        $route = $this->route('GET', $path, $controller);

        return $route;
    }

    /**
     * Shortcut to add route with POST method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function post(string $path, callable $controller): Route {
        $route = $this->route('POST', $path, $controller);

        return $route;
    }

    /**
     * Shortcut to add route with PUT method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function put(string $path, callable $controller): Route {
        $route = $this->route('PUT', $path, $controller);

        return $route;
    }

    /**
     * Shortcut to add route with PATCH method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function patch(string $path, callable $controller): Route {
        $route = $this->route('PATCH', $path, $controller);

        return $route;
    }

    /**
     * Shortcut to add route with DELETE method
     * 
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function delete(string $path, callable $controller): Route {
        $route = $this->route('DELETE', $path, $controller);

        return $route;
    }

    /**
     * Route definition
     * 
     * @param string $verb The allowed route http method
     * @param string $path The route path
     * @param callable $controller The route controller
     * @return Route
     * @throws UnsupportedRequestMethodException When the http request method isn't supported
     */
    public function route(string $verb, string $path, callable $controller): Route {
        $verb = strtoupper(trim($verb));
        $path = pathformat($path);

        if(!in_array($verb, Katya::SUPPORTED_VERBS)) {
            throw new UnsupportedRequestMethodException(sprintf('The HTTP method %s isn\'t allowed in route definition "%s".', $verb, $path));
        }

        $new_route = new Route($verb, $path, $controller);
        $this->routes[$verb][] = $new_route;

        return $new_route;
    }

    /**
     * Default controller to exec if don't match any route. Match any request method
     * 
     * @param callable $controller
     * @return void
     */
    public function default(callable $controller): void {
        $this->default_controller = $controller;
    }

    /**
     * Routes group definition under a common prefix
     * 
     * @param string $prefix Prefix for routes group
     * @param Closure $closure The routes definition
     * @return Group
     */
    public function group(string $prefix, Closure $closure): Group {
        $new_group = new Group($prefix, $closure, $this);
        $this->groups[] = $new_group;

        return $new_group;
    }

    /**
     * Set a variable
     * 
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function setVar(string $name, $value): void {
        $name = strtolower(trim($name));
        $this->vars[$name] = $value;
    }

    /**
     * Return a variable by name
     * 
     * @param string $name Variable name
     * @param mixed $default Optional default value to return
     * @return mixed
     */
    public function getVar(string $name, $default = null) {
        $name = strtolower(trim($name));
        return $this->vars[$name] ?? $default;
    }

    /**
     * Return true if a variable exists, otherwise false
     * 
     * @param string $name variable name
     * @return bool
     */
    public function hasVar(string $name): bool {
        $name = strtolower(trim($name));
        return array_key_exists($name, $this->vars);
    }

    /**
     * Start the router
     * 
     * @param Request $request The Request object with global params
     * @return void
     * @throws UnsupportedRequestMethodException When request method isn't supported
     * @throws RouteNotFoundException When request uri don't match any route
     */
    public function run(Request $request): void {
        static $invoke = false;

        if(!$invoke) {
            $this->processCors($request);
            $this->processGroups();
            $this->handleRequest($request);
            $invoke = true;
        }
    }

    /**
     * Enable Cross-Origin Resources Sharing
     * 
     * @param Request $request Request object
     * @return void
     */
    private function processCors($request): void {
        $server = $request->getServer();

        if ($server->has('HTTP_ORIGIN') && $server->get('HTTP_ORIGIN') != '') {
            foreach ($this->origins as $allowed_origin) {
                if (preg_match('#' . $allowed_origin . '#', $server->get('HTTP_ORIGIN'))) {
                    header('Access-Control-Allow-Origin: ' . $server->get('HTTP_ORIGIN'));
                    header('Access-Control-Allow-Methods: ' . implode(', ', Katya::SUPPORTED_VERBS));
                    header('Access-Control-Max-Age: 1000');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                    break;
                }
            }
        }
    }

    /**
     * Process the route groups before routing
     * 
     * @return void
     */
    private function processGroups(): void {
        if([] !== $this->groups) {
            foreach($this->groups as $group) {
                $group();
            }
        }
    }

    /**
     * Handle the request uri and start router
     * 
     * @param Request $request The Request object with global params
     * @return void
     * @throws UnsupportedRequestMethodException When the http method isn't allowed by router
     * @throws RouteNotFoundException When the request uri don't match any route
     */
    private function handleRequest(Request $request): void {
        $server = $request->getServer();
        $request_uri = $this->filterRequestUri($server->get('REQUEST_URI'));
        $request_method = $server->get('REQUEST_METHOD');

        if(!in_array($request_method, Katya::SUPPORTED_VERBS)) {
            throw new UnsupportedRequestMethodException(sprintf('The HTTP method %s isn\'t supported by router.', $request_method));
        }

        // Trailing slash no matters
        $request_uri = '/' !== $request_uri 
        ? rtrim($request_uri, '/\\') 
        : $request_uri;

        // Select the routes collection according to the http request method
        $routes = $this->routes[$request_method] ?? [];
        // Merge routes that match with any method
        $any = $this->routes['ANY'] ?? [];
        $routes = [...$routes, ...$any];

        foreach($routes as $route) {
            $full_path = $this->basepath.$route->getPath();
            
            if(preg_match($this->getPattern($full_path), $request_uri, $arguments)) {
                array_shift($arguments);
                list($params, $matches) = $this->filterArguments($arguments);
                $request->setParams($params);
                $request->setMatches($matches);

                $services = $this->services;

                // Filter the services for route
                if([] !== $route->getRouteServices() && isset($services)) {
                    $services = $this->filterServices($route->getRouteServices());
                }

                $response = new Response;
                if($this->viewspath) {
                    $response->viewspath = rtrim($this->viewspath, '/\\').'/';
                }


                // Exec the middleware
                if($route->hasHookBefore()) {
                    $before = $route->getHookBefore();
                    
                    $data = isset($services) 
                        ? call_user_func($before, $request, $response, $services)
                        : call_user_func($before, $request, $response);
                    
                    if(null !== $data) {
                        $request->setParam('@data', $data);
                    }
                }

                // Exec the controller
                isset($services) 
                    ? call_user_func($route->getController(), $request, $response, $services) 
                    : call_user_func($route->getController(), $request, $response);

                // Early return to end the routing
                return; 
            }
        }

        // Check for default controller. Match any route request
        if($this->default_controller) {
            isset($services) 
                ? call_user_func($this->default_controller, $request, new Response, $services) 
                : call_user_func($this->default_controller, $request, new Response);
            return;
        }

        // Exception for routes not found
        throw new RouteNotFoundException(sprintf('The request URI "%s" don\'t match any route.', $request_uri));
    }

    /**
     * Return the regex pattern for a string path
     * 
     * @param string $path String path
     * @return string
     */
    private function getPattern(string $path): string {
        $path = str_replace('/', '\/', pathformat($path));
        $path = preg_replace('#{(\w+)}#', '(?<$1>\w+)', $path); // Replace wildcards
        
        return '#^'.$path.'$#i';
    }

    /**
     * Filter a URI with GET params
     * 
     * @param string $uri The URI
     * @return string
     */
    private function filterRequestUri(string $uri): string {
        $uri = parse_url($uri, PHP_URL_PATH);
        return rawurldecode($uri);
    }

    /**
     * Filter an arguments array. Unnamed matches are pushed to a lineal array.
     * 
     * @param array $params The array to filter
     * @return array
     */
    private function filterArguments(array $params): array {
        $matches = [];

        foreach($params as $key => $item) {
            if(is_int($key)) {
                unset($params[$key]);
                $matches[] = $item;
            }
        }

        return [$params, $matches];
    }

    /**
     * Keep the services defined in list, otherwise unregister
     * 
     * @param string[] $names Service names to keep
     */
    private function filterServices(array $names): Services {
        $new_service = clone $this->services;
        $names = array_diff($new_service->keys(), $names);
        $new_service->unregister(...$names);

        return $new_service;
    }

}


/**
 * Convert a string to valid path format for the router
 * 
 * @param string $value String to convert
 * @return string
 */
function pathformat(string $value): string {
    return '/'.trim(trim($value), '/\\');
}

?>