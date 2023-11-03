<?php

namespace MoveMoveIo\Postmangen\Phpunit;

use MoveMoveIo\Postmangen\Options;
use MoveMoveIo\Postmangen\PostmangenConsts;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

class TestRunnerExecutionFinishedSubscriber implements ExecutionFinishedSubscriber
{
    private string $intermediateDir;
    private string $outputDir;

    public function __construct(string $intermediateDir, string $outputDir)
    {
        $this->intermediateDir = rtrim($intermediateDir, '/');
        $this->outputDir = rtrim($outputDir, '/');
    }

    public function notify(ExecutionFinished $event): void
    {
        if (Options::isAllTestsRun() && Options::allTestsSucceeded()) {
            $this->aggregateRequests();
        }
    }

    private function aggregateRequests(): void
    {
        $captures = $this->readIntermediateCaptures();
        if (count($captures) === 0) {
            return;
        }

        $collection = $this->renderCollection($captures);

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        $collectionJson = json_encode($collection, JSON_PRETTY_PRINT);
        $fileName = str_replace(' ', '_', strtolower($collection['info']['name']));

        file_put_contents("$this->outputDir/{$fileName}_generated.postman_collection.json", $collectionJson);
    }

    /**
     * @return array
     */
    private function readIntermediateCaptures(): array
    {
        $prefix = PostmangenConsts::TMP_FILE_PREFIX;
        $files = glob("$this->intermediateDir/$prefix*.json");
        $captures = [];

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $captures[] = json_decode($json, true);
            unlink($file); // delete intermediate file
        }
        return $captures;
    }

    /**
     * @param array $captures
     * @return array
     */
    private function renderCollection(array $captures): array
    {
        $collectionName = $_ENV['APP_NAME'] ?? 'Postman Collection';

        return [
            'info' => [
                'name' => $collectionName,
//                '_postman_id' => uniqid(),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->renderItems($captures),
        ];
    }

    /**
     * @param array $captures
     * @return void
     */
    private function renderItems(array $captures): array
    {
        $itemsWithoutFolder = [];
        $itemsByFolder = [];
        $processedUris = [];

        foreach ($captures as $capture) {
            if (!$capture) {
                continue;
            }

            $uri = str_replace("{", "{{", $capture['uri']);
            $uri = str_replace("}", "}}", $uri);
            $uriKey = "{$capture['method']} $uri";
            if (isset($processedUris[$uriKey]) && !isset($capture['must_capture'])) {
                continue;
            }
            $processedUris[$uriKey] = $uriKey;

            $itemRequest = $this->renderRequest($capture, $uri);
            $itemResponse = $this->renderResponse($capture, $itemRequest);
            $item = [
                'name' => $capture['route_name'] . " ($uri)",
                'request' => $itemRequest,
                'response' => $itemResponse,
            ];

            if (isset($capture['collection_folder'])) {
                $folderName = $capture['collection_folder'];
                if (!isset($itemsByFolder[$folderName])) {
                    $itemsByFolder[$folderName] = [];
                }
                $itemsByFolder[$folderName][] = $item;
            } else {
                $itemsWithoutFolder[] = $item;
            }
        }

        $folders = [];
        foreach ($itemsByFolder as $folderName => $folderItems) {
            $folders[] = [
                'name' => $folderName,
                'items' => $folderItems
            ];
        }

        return array_merge($folders, $itemsWithoutFolder);
    }

    /**
     * @param $request
     * @param $uri
     * @return array
     */
    private function renderRequest($request, $uri): array
    {
        $headers = $this->renderHeaders($request['headers']);
        return [
            'method' => $request['method'],
            'header' => $headers,
            'body' => empty($request['body']) ? [] : [
                'mode' => 'raw',
                'raw' => json_encode($request['body'], JSON_PRETTY_PRINT),
                "options" => [
                    "raw" => [
                        "language" => $this->detectPreviewLanguage($headers)
                    ],
                ],
            ],
            'url' => [
                'raw' => '{{protocol}}://{{api_host}}:{{api_port}}' . $uri,
                "protocol" => '{{protocol}}',
                "host" => [
                    '{{api_host}}'
                ],
                "port" => '{{api_port}}',
                "path" => array_values(array_filter(explode('/', $uri)))
            ],
        ];
    }

    /**
     * @param $capture
     * @param array $itemRequest
     * @return array[]
     */
    private function renderResponse($capture, array $itemRequest): array
    {
        $headers = $this->renderHeaders($capture['response_headers']);
        return [[
            'name' => $capture['response_status_code'] . ' ' . $capture['response_status_text'],
            'status' => $capture['response_status_text'],
            'code' => $capture['response_status_code'],
            '_postman_previewlanguage' => $this->detectPreviewLanguage($headers),
            'header' => $headers,
            'body' => json_encode(json_decode($capture['response_body'], true), JSON_PRETTY_PRINT),
            'originalRequest' => $itemRequest
        ]];
    }

    /**
     * @param $capturedHeaders
     * @return void
     */
    private function renderHeaders($capturedHeaders): array
    {
        $ignoredHeaders = [
            'date',
            'content-length',
            'user-agent',
            'host',
            'accept-language',
            'accept-charset'
        ];

        $headers = [];
        foreach ($capturedHeaders as $headerName => $values) {
            if (in_array(strtolower($headerName), $ignoredHeaders)) {
                continue;
            }
            foreach ($values as $val) {
                $headers [] = [
                    'key' => $headerName,
                    'value' => $val
                ];
            }
        }

        return $headers;
    }

    private function detectPreviewLanguage(array $headers): string
    {
        foreach ($headers as $header) {
            if (array_key_exists('key', $header) && strtolower($header['key']) == 'content-type') {
                $value = strtolower($header['value']);
                if (str_contains($value, 'json')) {
                    return 'json';
                }
                if (str_contains($value, 'html')) {
                    return 'html';
                }
                if (str_contains($value, 'xml')) {
                    return 'xml';
                }
            }
        }

        return 'auto';
    }
}
