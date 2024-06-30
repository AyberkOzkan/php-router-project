<?php

namespace App;

class Router {
    private $routes = [];
    private $methodNotAllowedCallback;
    private $notFoundCallback;
    private $basePath;
    private $middleware = [];

    public function __construct($basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get($route, $callback, $middleware = []) {
        $this->addRoute('GET', $route, $callback, $middleware);
    }

    public function post($route, $callback, $middleware = []) {
        $this->addRoute('POST', $route, $callback, $middleware);
    }

    private function addRoute($method, $route, $callback, $middleware) {
        $this->routes[] = [
            'method' => $method,
            'route' => $this->convertRouteToRegex($route),
            'callback' => $callback,
            'originalRoute' => $route,
            'middleware' => $middleware
        ];
    }

    private function convertRouteToRegex($route) {
        if (preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $matches)) {
            foreach ($matches[1] as $param) {
                $route = str_replace('{' . $param . '}', '(?P<' . $param . '>[^/]+)', $route);
            }
        }
        return '@^' . $route . '$@D';
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Base path'i URI'den çıkar
        if ($this->basePath && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }

        echo "Dispatching for method: $method and path: $path<br>";

        foreach ($this->routes as $route) {
            echo "Checking route: {$route['originalRoute']} with regex: {$route['route']}<br>";
            if ($method === $route['method'] && preg_match($route['route'], $path, $matches)) {
                echo "Route matched! Executing callback...<br>";
                array_shift($matches);
                
                // Middleware'i çalıştır
                foreach ($route['middleware'] as $middleware) {
                    $response = $middleware($method, $path);
                    if ($response) {
                        echo $response;
                        return;
                    }
                }

                return $this->invokeCallback($route['callback'], $matches);
            }
        }

        if ($this->methodNotAllowedCallback) {
            echo "Method not allowed callback triggered.<br>";
            call_user_func($this->methodNotAllowedCallback);
        } else {
            header("HTTP/1.0 405 Method Not Allowed");
        }

        if ($this->notFoundCallback) {
            echo "Not found callback triggered.<br>";
            call_user_func($this->notFoundCallback);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
    }

    private function invokeCallback($callback, $params) {
        if (is_callable($callback)) {
            return call_user_func_array($callback, array_values($params)); // params değerlerini array olarak gönderiyoruz
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            list($class, $method) = explode('@', $callback);
            if (class_exists($class) && method_exists($class, $method)) {
                return call_user_func_array([new $class, $method], array_values($params)); // params değerlerini array olarak gönderiyoruz
            }
        }
        throw new \Exception('Invalid callback');
    }

    public function setMethodNotAllowedCallback($callback) {
        $this->methodNotAllowedCallback = $callback;
    }

    public function setNotFoundCallback($callback) {
        $this->notFoundCallback = $callback;
    }
}
