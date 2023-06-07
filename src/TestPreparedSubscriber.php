<?php

namespace MoveMoveIo\Postmangen;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

class TestPreparedSubscriber implements PreparedSubscriber
{
    public function notify(Prepared $event): void
    {
        $test = $event->test();
        if ($test instanceof TestMethod) {
            config([
                PostmangenConsts::CONFIG_CURRENT_TEST_CLASS => $test->className(),
                PostmangenConsts::CONFIG_CURRENT_TEST_CLASS_METHOD => $test->methodName()
            ]);
        }
    }
}
