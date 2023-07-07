<?php

namespace MoveMoveIo\Postmangen\Phpunit;

use MoveMoveIo\Postmangen\Options;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

class TestErroredSubscriber implements ErroredSubscriber
{
    public function notify(Errored $event): void
    {
        Options::setAllTestsSucceeded(false);
    }
}
