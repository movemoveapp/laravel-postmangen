<?php

namespace MoveMoveIo\Postmangen\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use MoveMoveIo\Postmangen\PostmangenConsts;
use PHPUnit\Metadata\Annotation\Parser\DocBlock;
use PHPUnit\Metadata\Annotation\Parser\Registry;

class PostmangenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (App::environment() === 'testing') {
            $requestInfo = $this->prepareRequestInfo($request, $response);

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

    /**
     * @param Request $request
     * @param $response
     * @return array
     */
    private function prepareRequestInfo(Request $request, $response): array
    {
        $route = $request->route();
        $controllerName = collect(explode('\\', $route->getActionName()))->last();
        $controller = str_replace("Controller", "", $controllerName);
        $name = $route->getName() ?? $controller;
        $requestInfo = [
            'uri' => '/' . $route->uri(),
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

        $postmanCollectionFolder = $this->tryParsePostmanCollectionFolderAnnotation();
        if (!empty($postmanCollectionFolder)) {
            $requestInfo['collection_folder'] = $postmanCollectionFolder;
        }

        return $requestInfo;
    }

    private function tryParsePostmanCollectionFolderAnnotation()
    {
        $currentTestClass = config(PostmangenConsts::CONFIG_CURRENT_TEST_CLASS);
        if (empty($currentTestClass)) {
            return null;
        }

        $currentTestMethod = config(PostmangenConsts::CONFIG_CURRENT_TEST_CLASS_METHOD);
        if (!empty($currentTestMethod)) {
            $methodDocBlock = Registry::getInstance()->forMethod($currentTestClass, $currentTestMethod);
            $folder = $this->tryGetPostmanCollectionFolderFromDocBlock($methodDocBlock);
            if (!empty($folder)) {
                return $folder;
            }
        }

        $classDocBlock = Registry::getInstance()->forClassName($currentTestClass);
        $folder = $this->tryGetPostmanCollectionFolderFromDocBlock($classDocBlock);
        if (!empty($folder)) {
            return $folder;
        }

        return null;
    }

    private function tryGetPostmanCollectionFolderFromDocBlock(DocBlock $docBlock)
    {
        $methodAnnotations = $docBlock->symbolAnnotations();
        if (!array_key_exists(PostmangenConsts::ANNOTATION_POSTMAN_COLLECTION_FOLDER, $methodAnnotations) ||
            empty($methodAnnotations[PostmangenConsts::ANNOTATION_POSTMAN_COLLECTION_FOLDER])
        ) {
            return null;
        }
        $value = $methodAnnotations[PostmangenConsts::ANNOTATION_POSTMAN_COLLECTION_FOLDER];
        return is_array($value) ? $value[0] : $value;
    }
}