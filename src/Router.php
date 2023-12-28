<?php

use Swoole\Http\Request;
use Swoole\Http\Response;

class Router
{
    private $routes = [];

    public function get($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    public function put($path, $callback)
    {
        $this->addRoute('POST', $path, $callback);
    }

    public function delete($path, $callback)
    {
        $this->addRoute('GET', $path, $callback);
    }

    private function addRoute($method, $path, $callback)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
        ];
    }

    public function resolve($request, $response)
    {
        $path = $request->server['request_uri'];
        $method = $request->server['request_method'];

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->isPathMatch($route['path'], $path)) {
                $params = $this->extractParams($route['path'], $path);
                call_user_func($route['callback'], $request, $response, ...$params);
                return;
            }
        }

        $response->status(404);
        $response->end("Not Found");
    }

    private function isPathMatch($pattern, $path)
    {
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        return (bool) preg_match($pattern, $path);
    }

    private function extractParams($pattern, $path)
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        $params = [];
        foreach ($patternParts as $index => $part) {
            if (isset($pathParts[$index]) && !empty($part) && $part[0] === '{' && substr($part, -1) === '}') {
                $params[] = $pathParts[$index];
            }
        }

        return $params;
    }
}