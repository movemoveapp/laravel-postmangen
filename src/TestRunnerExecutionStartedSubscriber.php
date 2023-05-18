<?php

namespace MoveMoveIo\Postmangen;

use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;

class TestRunnerExecutionStartedSubscriber implements ExecutionStartedSubscriber
{
    private string $intermediateDir;

    public function __construct(string $intermediateDir)
    {
        $this->intermediateDir = trim($intermediateDir, '/');
    }

    public function notify(ExecutionStarted $event): void
    {
        // cleaning up temporary files in case any left from a previous run

        $prefix = PostmangenConsts::TMP_FILE_PREFIX;
        $files = glob("$this->intermediateDir/$prefix*.json");

        foreach ($files as $file) {
            unlink($file);
        }
    }
}
