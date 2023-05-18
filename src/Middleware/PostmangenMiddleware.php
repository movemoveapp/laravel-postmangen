<?php

namespace MoveMoveIo\Postmangen\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use MoveMoveIo\Postmangen\PostmangenConsts;

class PostmangenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (App::environment() === 'testing') {
            $route = $request->route();
            $controllerName = collect(explode('\\', $route->getActionName()))->last();
            $controller = str_replace("Controller", "", $controllerName);
            $name = $route->getName() ?? $controller;
            $requestInfo = [
                'uri' => '/'. $route->uri(),
                'route_name' => $name,
                'method' => $request->method(),
                'url' => $request->url(),
                'headers' => $request->headers->all(),
                'query' => $request->query(),
                'body' => $request->all(),

                'response_status_code' => $response->status(),
                'response_status_text' => $response->statusText(),
                'response_headers' => $response->headers->all(),
                'response_body' => $response->getContent(),
            ];

            // Generate filename with current timestamp
            $filename = PostmangenConsts::TMP_FILE_PREFIX . microtime(true) . '.json';

            $outputDir = trim(env('POSTMANGEN_TMP'), '/');
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0777, true);
            }

            // Save request information to file
            file_put_contents($outputDir . '/' . $filename, json_encode($requestInfo));
        }

        return $response;
    }
}