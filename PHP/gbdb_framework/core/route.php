<?php

class Route {

    private static array $routes = [];
    private static array $middlewares = [];
    private static $notFoundHandler = null;
    private static $methodNotAllowedHandler = null;
    private static $currentMiddleware = [];

    /** Mittels Middleware registrieren */
    /**
     * Verarbeitet die Funktion middleware.
     * @param callable $mw Übergabewert.
     * @return self Rückgabewert.
     */
    public static function middleware(callable $mw): self {
        self::$currentMiddleware[] = $mw;
        return new self;
    }

    /** GET */
    /**
     * Liest Daten aus der angegebenen Quelle.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function get(string $path, callable $callback): void {
        self::addRoute("GET", $path, $callback);
    }

    /** POST */
    /**
     * Verarbeitet die Funktion post.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function post(string $path, callable $callback): void {
        self::addRoute("POST", $path, $callback);
    }

    /** PUT */
    /**
     * Verarbeitet die Funktion put.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function put(string $path, callable $callback): void {
        self::addRoute("PUT", $path, $callback);
    }

    /** DELETE */
    /**
     * Löscht Daten aus der angegebenen Quelle.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function delete(string $path, callable $callback): void {
        self::addRoute("DELETE", $path, $callback);
    }

    /** PATCH */
    /**
     * Verarbeitet die Funktion patch.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function patch(string $path, callable $callback): void {
        self::addRoute("PATCH", $path, $callback);
    }

    /** Custom 404 */
    /**
     * Verarbeitet die Funktion not found.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function notFound(callable $callback): void {
        self::$notFoundHandler = $callback;
    }

    /** Custom 405 */
    /**
     * Verarbeitet die Funktion method not allowed.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    public static function methodNotAllowed(callable $callback): void {
        self::$methodNotAllowedHandler = $callback;
    }

    /** (Privat) Route registrieren */
    /**
     * Verarbeitet die Funktion add route.
     * @param string $method Übergabewert.
     * @param string $path Übergabewert.
     * @param callable $callback Übergabewert.
     * @return void Rückgabewert.
     */
    private static function addRoute(string $method, string $path, callable $callback): void {
        self::$routes[$method][$path] = [
            "callback" => $callback,
            "middleware" => self::$currentMiddleware
        ];

        // Middleware-Stack resetten
        self::$currentMiddleware = [];
    }

    /** Dispatcher */
    /**
     * Verarbeitet die Funktion dispatch.
     * @return void Rückgabewert.
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
                        continue; // falsche Methode
                    }

                    array_shift($matches);

                    // Middlewares ausführen
                    foreach ($data["middleware"] as $mw) {
                        if ($mw() === false) {
                            return; // Middleware blockiert
                        }
                    }

                    // Callback ausführen
                    $response = call_user_func_array($data["callback"], $matches);

                    // JSON Response Auto-Mode
                    if (is_array($response)) {
                        Http::jsonResponse($response);
                        return;
                    }

                    echo $response;

                    return;
                }
            }
        }

        // Route existiert, aber falsche Methode?
        if (!empty($allowedForUri)) {
            if (self::$methodNotAllowedHandler) {
                echo call_user_func(self::$methodNotAllowedHandler);
                return;
            }

            http_response_code(405);
            echo "405 - Methode nicht erlaubt";

            return;
        }

        // 404
        if (self::$notFoundHandler) {
            echo call_user_func(self::$notFoundHandler);
            return;
        }

        http_response_code(404);
        echo "404 - Route nicht gefunden";
    }

    /** Parameter-Pattern konvertieren */
    /**
     * Verarbeitet die Funktion make pattern.
     * @param string $path Übergabewert.
     * @return string Rückgabewert.
     */
    private static function makePattern(string $path): string {
        // Optional param: {id?}
        $path = preg_replace('/\{([^\/\}]+)\?\}/', '(?:\/([^\/]+))?', $path);

        // Pflichtparam: {id}
        $path = preg_replace('/\{([^\/\}]+)\}/', '([^\/]+)', $path);

        return "@^" . str_replace('/', '\/', $path) . "$@";
    }
}
