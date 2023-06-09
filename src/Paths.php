<?php

namespace MoveMoveIo\Postmangen;

class Paths
{
    private string $postmangenSrcPath;
    private string $appBasePath;

    private static Paths $instance;
    private static function init() {
        self::$instance = new Paths();
    }

    private function __construct()
    {
        $this->postmangenSrcPath = __DIR__;
        $this->appBasePath = dirname($this->postmangenSrcPath, 4);
    }

    private function _appBasePath(string $path = ''): string
    {
        return $this->appBasePath . ($path != '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public static function appBasePath(string $path = ''): string
    {
        return self::$instance->_appBasePath($path);
    }
}

(static function () {
    static::init();
})->bindTo(null, Paths::class)();