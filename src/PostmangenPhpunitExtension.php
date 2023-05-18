<?php

namespace MoveMoveIo\Postmangen;

use PHPUnit\Runner\Extension\Extension as PhpunitExtension;
use PHPUnit\Runner\Extension\Facade as EventFacade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class PostmangenPhpunitExtension implements PhpunitExtension
{
    public function bootstrap(Configuration $configuration, EventFacade $facade, ParameterCollection $parameters): void
    {
        $outputDir = $parameters->has('outputDir') ? $parameters->get('outputDir') : 'postman/';
        $intermediateDir = $_ENV['POSTMANGEN_TMP'] ?: ($parameters->has('intermediateDir') ? $parameters->get('intermediateDir') : $outputDir);

        $facade->registerSubscriber(new TestRunnerExecutionStartedSubscriber($intermediateDir));
        $facade->registerSubscriber(new TestRunnerExecutionFinishedSubscriber($intermediateDir, $outputDir));
    }
}