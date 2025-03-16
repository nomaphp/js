<?php

namespace Noma\Js\Tests;

use Noma\Js\Js;
use PHPUnit\Framework\TestCase;

class ExamplesTest extends TestCase
{
    public function testExample1(): void
    {
        $js = Js::fromFile(__DIR__ . '/../examples/example-1.php');
        $expected = file_get_contents(__DIR__ . '/../examples/example-1.js');


        $this->assertEquals($expected, $js);
    }

    public function testExample2(): void
    {
        $js = Js::fromFile(__DIR__ . '/../examples/example-2.php');
        $expected = file_get_contents(__DIR__ . '/../examples/example-2.js');

        $this->assertEquals($expected, $js);
    }

    public function testComplexReact(): void
    {
        $js = Js::fromFile(__DIR__ . '/../examples/complex/react.php');
        $expected = file_get_contents(__DIR__ . '/../examples/complex/react.js');

        $this->assertEquals($expected, $js);
    }
}