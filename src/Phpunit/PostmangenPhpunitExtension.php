<?php

namespace MoveMoveIo\Postmangen\Phpunit;

use MoveMoveIo\Postmangen\Options;
use MoveMoveIo\Postmangen\Paths;
use PHPUnit\Runner\Extension\Extension as PhpunitExtension;
use PHPUnit\Runner\Extension\Facade as EventFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class PostmangenPhpunitExtension implements PhpunitExtension
{
    public function bootstrap(Configuration $configuration, EventFacade $facade, ParameterCollection $parameters): void
    {
        if (!Options::isAllTestsRun()) {
            return;
        }
        $outputDir = Paths::appBasePath($parameters->has('outputDir') ? $parameters->get('outputDir') : 'postman/');
        $intermediateDir = Paths::appBasePath($_ENV['POSTMANGEN_TMP'] ?: ($parameters->has('intermediateDir') ? $parameters->get('intermediateDir') : $outputDir));

        $facade->registerSubscriber(new TestRunnerExecutionStartedSubscriber($intermediateDir));
        $facade->registerSubscriber(new TestRunnerExecutionFinishedSubscriber($intermediateDir, $outputDir));
        $facade->registerSubscriber(new TestPreparedSubscriber());
        $facade->registerSubscriber(new TestFailedSubscriber());
    }
}