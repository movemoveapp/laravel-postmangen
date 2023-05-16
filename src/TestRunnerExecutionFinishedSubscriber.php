<?php

namespace MoveMoveIo\Postmangen;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

class TestRunnerExecutionFinishedSubscriber implements ExecutionFinishedSubscriber
{
    private string $intermediateDir;
    private string $outputDir;

    public function __construct(string $intermediateDir, string $outputDir)
    {
        $this->intermediateDir = trim($intermediateDir, '/');
        $this->outputDir = trim($outputDir, '/');
    }

    public function notify(ExecutionFinished $event): void
    {
        $this->aggregateRequests();
    }

    private function aggregateRequests(): void
    {
        $files = glob("$this->intermediateDir/tmp-request-*.json");
        $requests = [];

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $requests[] = json_decode($json, true);
            unlink($file); // delete intermediate file
        }

        $collectionName = $_ENV['APP_NAME'] ?? 'Postman Collection';

        $collection = [
            'info' => [
                'name' => $collectionName,
                '_postman_id' => uniqid(),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $processedUris = [];

        foreach ($requests as $request) {
            if (!$request) {
                continue;
            }

            $uri = str_replace("{", "{{", $request['uri']);
            $uri = str_replace("}", "}}", $uri);
            $uriKey = "{$request['method']} $uri";
            if (isset($processedUris[$uriKey])) {
                continue;
            }
            $processedUris[$uriKey] = $uriKey;

            $item = [
                'name' => $request['route_name'] . " ($uri)",
                'request' => [
                    'method' => $request['method'],
                    'header' => [
                        [
                            'key' => 'Content-Type',
                            'value' => 'application/json',
                        ],
                    ],
                    'body' => empty($request['body']) ? [] : [
                        'mode' => 'raw',
                        'raw' => json_encode($request['body'], JSON_PRETTY_PRINT),
                        "options" => [
                            "raw" => [
                                "language" => "json"
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
                ],
            ];

            $collection['item'][] = $item;
        }

        $collectionJson = json_encode($collection, JSON_PRETTY_PRINT);

        $fileName = str_replace(' ', '_', strtolower($collectionName));

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        file_put_contents("$this->outputDir/$fileName.postman_collection.json", $collectionJson);
    }
}
