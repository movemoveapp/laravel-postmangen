<?php

namespace MoveMoveIo\Postmangen;

class Options
{
    private bool $isFullTestBenchRun;

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
}
(static function () {
    static::init();
})->bindTo(null, Options::class)();