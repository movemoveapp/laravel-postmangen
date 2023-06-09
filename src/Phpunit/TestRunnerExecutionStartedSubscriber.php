<?php

namespace MoveMoveIo\Postmangen\Phpunit;

use MoveMoveIo\Postmangen\PostmangenConsts;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;

class TestRunnerExecutionStartedSubscriber implements ExecutionStartedSubscriber
{
    private string $intermediateDir;

    public function __construct(string $intermediateDir)
    {
        $this->intermediateDir = rtrim($intermediateDir, '/');
    }

    public function notify(ExecutionStarted $event): void
    {
        // cleaning up temporary files in case any left from a previous run

        if (!is_dir($this->intermediateDir)) {
            return;
        }

        $files = glob("$this->intermediateDir/*");

        foreach ($files as $file) {
            unlink($file);
        }
    }
}
