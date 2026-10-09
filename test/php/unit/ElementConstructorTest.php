<?php

use PHPUnit\Framework\TestCase;

require __DIR__ . '/../../../system/classes/html/Element.php';

final class ElementConstructorTest extends TestCase
{
    public function testConstructorWithoutSetter()
    {
        $elem = new TestElement([
            "noSetter" => "test",
        ]);
        $this->assertSame($elem->noSetter, "test");
    }

    public function testConstructorWithSetter()
    {
        $elem = new TestElement([
            "withSetter" => "test",
        ]);
        $this->assertSame($elem->withSetter, 1);
    }

    public function testConstructorMultiple()
    {
        $elem = new TestElement([
            "withSetter|noSetter" => "test",
        ]);
        $this->assertSame($elem->withSetter, 1);
        $this->assertSame($elem->noSetter, "test");
    }
}

class TestElement extends Html\Element
{
    public $noSetter;

    public $withSetter;

    public function setWithSetter($var)
    {
        $this->withSetter = 1;
    }
}
