<?php

namespace MoveMoveIo\Postmangen\Phpunit;

use MoveMoveIo\Postmangen\Options;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

class TestFailedSubscriber implements FailedSubscriber
{
    public function notify(Failed $event): void
    {
        Options::setAllTestsSucceeded(false);
    }
}
