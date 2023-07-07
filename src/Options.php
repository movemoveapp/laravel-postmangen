<?php

namespace MoveMoveIo\Postmangen;

class Options
{
    private bool $isFullTestBenchRun;
    private bool $allTestsSucceeded = true;

    private static Options $instance;
    private static function init() {
        self::$instance = new Options();
    }

    private function __construct()
    {
        global $argv;
        $opts = array_intersect($argv, ['--filter', '--test-suffix']);
        $this->isFullTestBenchRun = count($opts) == 0;
    }

    public static function isAllTestsRun(): bool
    {
        return self::$instance->isFullTestBenchRun;
    }

    public static function allTestsSucceeded(): bool
    {
        return self::$instance->allTestsSucceeded;
    }

    public static function setAllTestsSucceeded(bool $allTestsSucceeded): void
    {
        self::$instance->allTestsSucceeded = $allTestsSucceeded;
    }
}
(static function () {
    static::init();
})->bindTo(null, Options::class)();