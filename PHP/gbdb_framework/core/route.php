<?php

class Route {

    private static array $routes = [];
    private static array $middlewares = [];
    private static $notFoundHandler = null;
    private static $methodNotAllowedHandler = null;
    private static $currentMiddleware = [];

    /**
     * registers middleware
     * @param callable $mw
     * @return Route
     */
    public static function middleware(callable $mw): self {
        self::$currentMiddleware[] = $mw;

        return new self;
    }

    /**
     * GET
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public static function get(string $path, callable $callback): void {
        self::addRoute("GET", $path, $callback);
    }

    /**
     * POST
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public static function post(string $path, callable $callback): void {
        self::addRoute("POST", $path, $callback);
    }

    /**
     * PUT
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public static function put(string $path, callable $callback): void {
        self::addRoute("PUT", $path, $callback);
    }

    /**
     * DELETE
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public static function delete(string $path, callable $callback): void {
        self::addRoute("DELETE", $path, $callback);
    }

    /**
     * PATCH
     * @param string $path
     * @param callable $callback
     * @return void
     */
    public static function patch(string $path, callable $callback): void {
        self::addRoute("PATCH", $path, $callback);
    }

    /**
     * costum 404
     * @param callable $callback
     * @return void
     */
    public static function notFound(callable $callback): void {
        self::$notFoundHandler = $callback;
    }

    /**
     * costum 405
     * @param callable $callback
     * @return void
     */
    public static function methodNotAllowed(callable $callback): void {
        self::$methodNotAllowedHandler = $callback;
    }

    private static function addRoute(string $method, string $path, callable $callback): void {
        self::$routes[$method][$path] = [
            "callback" => $callback,
            "middleware" => self::$currentMiddleware
        ];

        self::$currentMiddleware = [];
    }

    /**
     * route dispatcher
     * @return void
     */
    public static function dispatch(): void {
        $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        $allowedForUri = [];

        foreach (self::$routes as $routeMethod => $routes) {
            foreach ($routes as $path => $data) {

                $regex = self::makePattern($path);

                if (preg_match($regex, $uri, $matches)) {
                    $allowedForUri[] = $routeMethod;

                    if ($routeMethod !== $method) {
                        continue;
                    }

                    array_shift($matches);

                    foreach ($data["middleware"] as $mw) {
                        if ($mw() === false) {
                            return;
                        }

                    }

                    $response = call_user_func_array($data["callback"], $matches);

                    if (is_array($response)) {
                        Http::jsonResponse($response);

                        return;
                    }

                    echo $response;

                    return;
                }

            }

        }

        if (!empty($allowedForUri)) {
            if (self::$methodNotAllowedHandler) {
                echo call_user_func(self::$methodNotAllowedHandler);

                return;
            }

            http_response_code(405);
            echo "405 - Methode nicht erlaubt";

            return;
        }

        if (self::$notFoundHandler) {
            echo call_user_func(self::$notFoundHandler);

            return;
        }

        http_response_code(404);
        echo "404 - Route nicht gefunden";
    }

    private static function makePattern(string $path): string {
        // Optional param: {id?}
        $path = preg_replace('/\{([^\/\}]+)\?\}/', '(?:\/([^\/]+))?', $path);

        // Pflichtparam: {id}
        $path = preg_replace('/\{([^\/\}]+)\}/', '([^\/]+)', $path);

        return "@^" . str_replace('/', '\/', $path) . "$@";
    }

}
